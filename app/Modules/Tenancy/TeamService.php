<?php

namespace App\Modules\Tenancy;

use App\Models\User;
use App\Modules\Audit\AuditRecorder;
use App\Modules\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public const ASSIGNABLE_ROLES = ['manager', 'agent', 'auditor'];

    /** @return array{id: string, token: string} */
    public function invite(int $actorId, string $email, string $role): array
    {
        $this->validateRole($role);
        $email = strtolower(trim($email));

        return DB::transaction(function () use ($actorId, $email, $role): array {
            $tenantId = $this->lockOwnedWorkspace($actorId);
            $alreadyMember = DB::table('tenant_memberships')->join('users', 'users.id', '=', 'tenant_memberships.user_id')
                ->where('tenant_memberships.tenant_id', $tenantId)->whereRaw('LOWER(users.email) = ?', [$email])->exists();
            if ($alreadyMember) {
                throw ValidationException::withMessages(['email' => 'This person is already a member. Change their role in the member list.']);
            }
            // Reissuing invalidates every older pending link for this recipient.
            $superseded = DB::table('workspace_invitations')->where('tenant_id', $tenantId)->where('email', $email)->where('status', 'pending')->get(['id']);
            foreach ($superseded as $old) {
                DB::table('workspace_invitations')->where('tenant_id', $tenantId)->where('id', $old->id)->update(['status' => 'revoked', 'revoked_at' => now(), 'updated_at' => now()]);
                app(AuditRecorder::class)->record($actorId, 'invitation.superseded', 'invitation', $old->id);
            }
            $id = (string) Str::ulid();
            $token = bin2hex(random_bytes(32));
            DB::table('workspace_invitations')->insert([
                'id' => $id, 'tenant_id' => $tenantId, 'email' => $email, 'role' => $role,
                'token_hash' => hash('sha256', $token), 'status' => 'pending', 'invited_by' => $actorId,
                'expires_at' => now()->addHours(72), 'created_at' => now(), 'updated_at' => now(),
            ]);
            app(AuditRecorder::class)->record($actorId, 'invitation.created', 'invitation', $id, ['role' => $role]);

            return ['id' => $id, 'token' => $token];
        });
    }

    public function revoke(int $actorId, string $invitationId): void
    {
        DB::transaction(function () use ($actorId, $invitationId): void {
            $tenantId = $this->lockOwnedWorkspace($actorId);
            $row = DB::table('workspace_invitations')->where('tenant_id', $tenantId)->where('id', $invitationId)->lockForUpdate()->first();
            abort_if($row === null, 404);
            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['invitation' => 'Only pending invitations can be revoked.']);
            }
            DB::table('workspace_invitations')->where('tenant_id', $tenantId)->where('id', $invitationId)->update(['status' => 'revoked', 'revoked_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($actorId, 'invitation.revoked', 'invitation', $invitationId);
        });
    }

    public function changeRole(int $actorId, int $memberId, string $role, string $expectedRole): void
    {
        $this->validateRole($role);
        DB::transaction(function () use ($actorId, $memberId, $role, $expectedRole): void {
            $tenantId = $this->lockOwnedWorkspace($actorId);
            $member = DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('id', $memberId)->lockForUpdate()->first();
            abort_if($member === null, 404);
            $this->validateMemberChange($member->role, $expectedRole);
            if ($member->role === $role) {
                return;
            }
            DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('id', $memberId)->update(['role' => $role, 'updated_at' => now()]);
            app(AuditRecorder::class)->record($actorId, 'membership.role_changed', 'membership', (string) $memberId, ['from' => $member->role, 'to' => $role]);
        });
    }

    public function remove(int $actorId, int $memberId, string $expectedRole): void
    {
        DB::transaction(function () use ($actorId, $memberId, $expectedRole): void {
            $tenantId = $this->lockOwnedWorkspace($actorId);
            $member = DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('id', $memberId)->lockForUpdate()->first();
            abort_if($member === null, 404);
            $this->validateMemberChange($member->role, $expectedRole);
            DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('id', $memberId)->delete();
            app(AuditRecorder::class)->record($actorId, 'membership.removed', 'membership', (string) $memberId, ['role' => $member->role]);
        });
    }

    /** @return array{name: string, role: string, expires_at: string, active: bool} */
    public function preview(Tenant $tenant, string $invitationId, User $user): array
    {
        $row = DB::table('workspace_invitations')->where('tenant_id', $tenant->id)->where('id', $invitationId)->first();
        abort_if($row === null, 404);
        $this->validateRecipient($row->email, $user);

        return ['name' => $tenant->name, 'role' => $row->role, 'expires_at' => $row->expires_at,
            'active' => $row->status === 'pending' && CarbonImmutable::parse($row->expires_at)->greaterThan(now())];
    }

    public function accept(Tenant $tenant, string $invitationId, string $token, User $user): void
    {
        DB::transaction(function () use ($tenant, $invitationId, $token, $user): void {
            DB::table('tenants')->where('id', $tenant->id)->lockForUpdate()->firstOrFail();
            $row = DB::table('workspace_invitations')->where('tenant_id', $tenant->id)->where('id', $invitationId)->lockForUpdate()->first();
            abort_if($row === null, 404);
            abort_unless(hash_equals($row->token_hash, hash('sha256', $token)), 404);
            $freshUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->validateRecipient($row->email, $freshUser);
            $this->validatePending($row->status, $row->expires_at);
            $this->validateRole($row->role);
            $inviterIsOwner = DB::table('tenant_memberships')->where('tenant_id', $tenant->id)->where('user_id', $row->invited_by)->where('role', 'owner')->exists();
            abort_unless($inviterIsOwner, 403);
            $alreadyMember = DB::table('tenant_memberships')->where('tenant_id', $tenant->id)->where('user_id', $freshUser->id)->exists();
            if ($alreadyMember) {
                throw ValidationException::withMessages(['invitation' => 'You already belong to this business. Invitations cannot change existing permissions.']);
            }
            DB::table('tenant_memberships')->insert(['tenant_id' => $tenant->id, 'user_id' => $freshUser->id, 'role' => $row->role, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('workspace_invitations')->where('tenant_id', $tenant->id)->where('id', $invitationId)->update(['status' => 'accepted', 'accepted_by' => $freshUser->id, 'accepted_at' => now(), 'updated_at' => now()]);
            $context = app(TenantContext::class);
            $context->set($tenant, $row->role);
            try {
                app(AuditRecorder::class)->record($freshUser->id, 'invitation.accepted', 'invitation', $invitationId, ['role' => $row->role]);
            } finally {
                $context->clear();
            }
        });
    }

    private function lockOwnedWorkspace(int $actorId): string
    {
        $tenantId = app(TenantContext::class)->tenant()->id;
        DB::table('tenants')->where('id', $tenantId)->lockForUpdate()->firstOrFail();
        // Recheck after locking: permission may have changed after middleware ran.
        $owner = DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('user_id', $actorId)->where('role', 'owner')->exists();
        abort_unless($owner, 403);

        return $tenantId;
    }

    private function validateRole(string $role): void
    {
        if (! in_array($role, self::ASSIGNABLE_ROLES, true)) {
            throw ValidationException::withMessages(['role' => 'Choose manager, agent or auditor. Ownership cannot be assigned here.']);
        }
    }

    private function validateMemberChange(string $role, string $expectedRole): void
    {
        if ($role === 'owner') {
            throw ValidationException::withMessages(['role' => 'Owners cannot be removed or demoted here. A separate ownership-transfer process is required.']);
        }
        if ($role !== $expectedRole) {
            throw ValidationException::withMessages(['role' => 'This member changed. Reload before making another change.']);
        }
    }

    private function validateRecipient(string $email, User $user): void
    {
        abort_unless($user->hasVerifiedEmail() && strtolower(trim($user->email)) === $email, 404);
    }

    private function validatePending(string $status, string $expiresAt): void
    {
        if ($status !== 'pending' || CarbonImmutable::parse($expiresAt)->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['invitation' => 'This invitation is expired or no longer active. Ask the owner for a new link.']);
        }
    }
}
