<?php

namespace Tests\Postgres;

use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TeamService;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    private string $primary;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->primary = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires PostgreSQL and two independent connections; executed by the CI pgsql job.');
        }
        config(['database.connections.team_competitor' => config('database.connections.'.$this->primary)]);
        DB::connection('team_competitor')->statement("SET lock_timeout = '100ms'");
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Concurrency Test', 'locale' => 'bn']);
        $this->addMember($this->owner, 'owner');
    }

    protected function tearDown(): void
    {
        if (isset($this->primary)) {
            DB::setDefaultConnection($this->primary);
            while (DB::connection()->transactionLevel() > 0) {
                DB::connection()->rollBack();
            }
            DB::purge('team_competitor');
            app(TenantContext::class)->clear();
        }
        parent::tearDown();
    }

    private function addMember(User $user, string $role): int
    {
        return DB::table('tenant_memberships')->insertGetId(['tenant_id' => $this->tenant->id, 'user_id' => $user->id, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function compete(callable $operation): void
    {
        DB::setDefaultConnection('team_competitor');
        try {
            $operation();
        } finally {
            DB::setDefaultConnection($this->primary);
            app(TenantContext::class)->clear();
        }
    }

    private function assertLocked(callable $operation): void
    {
        try {
            $this->compete($operation);
            $this->fail('A competing operation must not pass an uncommitted team mutation.');
        } catch (QueryException $exception) {
            $this->assertSame('55P03', $exception->errorInfo[0]);
        }
    }

    public function test_uncommitted_accept_blocks_a_second_connection_and_retry_cannot_accept_twice(): void
    {
        $context = app(TenantContext::class);
        $service = app(TeamService::class);
        $recipient = User::factory()->create(['email' => 'recipient@example.test']);
        $context->set($this->tenant, 'owner');
        $invitation = $service->invite($this->owner->id, $recipient->email, 'agent');
        $context->clear();

        // The outer transaction retains the real service's locks and uncommitted writes.
        DB::beginTransaction();
        $service->accept($this->tenant, $invitation['id'], $invitation['token'], $recipient);
        $this->assertLocked(fn () => $service->accept($this->tenant, $invitation['id'], $invitation['token'], $recipient));
        $other = DB::connection('team_competitor');
        $this->assertFalse($other->table('tenant_memberships')->where('tenant_id', $this->tenant->id)->where('user_id', $recipient->id)->exists());
        $this->assertSame('pending', $other->table('workspace_invitations')->where('tenant_id', $this->tenant->id)->where('id', $invitation['id'])->value('status'));
        DB::commit();

        try {
            $this->compete(fn () => $service->accept($this->tenant, $invitation['id'], $invitation['token'], $recipient));
            $this->fail('A retry must reject an already accepted invitation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('invitation', $exception->errors());
        }
        $this->assertSame(1, DB::table('tenant_memberships')->where('tenant_id', $this->tenant->id)->where('user_id', $recipient->id)->count());
        $this->assertSame(1, DB::table('audit_logs')->where('tenant_id', $this->tenant->id)->where('subject_id', $invitation['id'])->where('action', 'invitation.accepted')->count());
        $this->assertSame(0, $other->transactionLevel());
    }

    public function test_role_change_blocks_competing_removal_and_stale_retry_is_rejected(): void
    {
        $memberId = $this->addMember(User::factory()->create(), 'agent');
        $context = app(TenantContext::class);
        $service = app(TeamService::class);
        $context->set($this->tenant, 'owner');
        DB::beginTransaction();
        $service->changeRole($this->owner->id, $memberId, 'auditor', 'agent');
        $this->assertLocked(function () use ($context, $service, $memberId): void {
            $context->set($this->tenant, 'owner');
            $service->remove($this->owner->id, $memberId, 'agent');
        });
        DB::commit();
        try {
            $this->compete(function () use ($context, $service, $memberId): void {
                $context->set($this->tenant, 'owner');
                $service->remove($this->owner->id, $memberId, 'agent');
            });
            $this->fail('Stale removal must not delete a member whose role changed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('role', $exception->errors());
        }
        $this->assertSame('auditor', DB::table('tenant_memberships')->where('tenant_id', $this->tenant->id)->where('id', $memberId)->value('role'));
        $this->assertSame(0, DB::table('audit_logs')->where('tenant_id', $this->tenant->id)->where('subject_id', (string) $memberId)->where('action', 'membership.removed')->count());
    }
}
