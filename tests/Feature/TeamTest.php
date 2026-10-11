<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\AuditRecorder;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TeamService;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Test Business', 'locale' => 'bn']);
        $this->addMember($this->owner, 'owner');
    }

    private function addMember(User $user, string $role): int
    {
        return DB::table('tenant_memberships')->insertGetId(['tenant_id' => $this->tenant->id, 'user_id' => $user->id, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function base(): string
    {
        return "/workspaces/{$this->tenant->id}/team";
    }

    /** @return array{id: string, token: string, link: string} */
    private function invite(string $email = 'recipient@example.test', string $role = 'agent'): array
    {
        $this->actingAs($this->owner)->post($this->base().'/invitations', ['email' => $email, 'role' => $role])->assertRedirect($this->base());
        $encrypted = session('team_invitation_link');
        $link = Crypt::decryptString($encrypted);
        $row = DB::table('workspace_invitations')->where('tenant_id', $this->tenant->id)->where('email', strtolower($email))->orderByDesc('id')->first();

        return ['id' => $row->id, 'token' => substr(parse_url($link, PHP_URL_FRAGMENT), 6), 'link' => $link];
    }

    private function joinUrl(string $id): string
    {
        return "/join/{$this->tenant->id}/{$id}";
    }

    public function test_owner_can_invite_and_database_only_stores_token_hash(): void
    {
        $invitation = $this->invite('RECIPIENT@example.test');
        $row = DB::table('workspace_invitations')->where('tenant_id', $this->tenant->id)->where('id', $invitation['id'])->first();
        $this->assertSame(hash('sha256', $invitation['token']), $row->token_hash);
        $this->assertSame('recipient@example.test', $row->email);
        $this->assertStringNotContainsString($invitation['token'], json_encode($row));
        $this->assertStringContainsString('#token=', $invitation['link']);
        $audit = DB::table('audit_logs')->where('tenant_id', $this->tenant->id)->where('subject_id', $row->id)->first();
        $this->assertStringNotContainsString('recipient@example.test', $audit->metadata);
        $this->assertStringNotContainsString($invitation['token'], $audit->metadata);
    }

    public function test_link_is_one_time_flash_and_not_a_team_page_prop(): void
    {
        $invitation = $this->invite();
        $this->assertStringNotContainsString($invitation['token'], session('team_invitation_link'));
        $response = $this->get($this->base())->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertInertia(fn (Assert $page) => $page->component('team/index')->hasFlash('invitationLink', $invitation['link'])->missing('invitationLink')->missing('invitations.data.0.token_hash'));
        $this->assertNull(session('team_invitation_link'));
        $this->get($this->base())->assertInertia(fn (Assert $page) => $page->missing('invitationLink')->missingFlash('invitationLink'));
    }

    public function test_non_owners_cannot_manage_the_team_or_escalate_roles(): void
    {
        foreach (['manager', 'agent', 'auditor'] as $role) {
            $user = User::factory()->create();
            $memberId = $this->addMember($user, $role);
            $this->actingAs($user)->get($this->base())->assertForbidden();
            $this->post($this->base().'/invitations', ['email' => 'new@example.test', 'role' => 'manager'])->assertForbidden();
            $this->patch($this->base()."/members/{$memberId}", ['role' => 'manager', 'current_role' => $role])->assertForbidden();
            $this->delete($this->base()."/members/{$memberId}", ['current_role' => $role])->assertForbidden();
        }
    }

    public function test_non_members_cannot_view_or_invite_into_another_business(): void
    {
        $this->actingAs(User::factory()->create())->get($this->base())->assertNotFound();
        $this->post($this->base().'/invitations', ['email' => 'new@example.test', 'role' => 'agent'])->assertNotFound();
    }

    public function test_owner_role_and_unknown_roles_cannot_be_invited(): void
    {
        foreach (['owner', 'superadmin', 'arbitrary'] as $role) {
            $this->actingAs($this->owner)->postJson($this->base().'/invitations', ['email' => 'new@example.test', 'role' => $role])->assertUnprocessable()->assertJsonValidationErrors('role');
        }
        $this->assertDatabaseCount('workspace_invitations', 0);
    }

    public function test_matching_verified_recipient_accepts_exactly_once(): void
    {
        $invitation = $this->invite();
        $user = User::factory()->create(['email' => 'recipient@example.test']);
        $this->actingAs($user)->get($this->joinUrl($invitation['id']))->assertInertia(fn (Assert $page) => $page->component('team/accept')->where('invitation.active', true));
        $this->post($this->joinUrl($invitation['id']), ['token' => $invitation['token']])->assertRedirect("/workspaces/{$this->tenant->id}");
        $this->assertDatabaseHas('tenant_memberships', ['tenant_id' => $this->tenant->id, 'user_id' => $user->id, 'role' => 'agent']);
        $this->postJson($this->joinUrl($invitation['id']), ['token' => $invitation['token']])->assertUnprocessable();
        $this->assertSame(1, DB::table('tenant_memberships')->where('tenant_id', $this->tenant->id)->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'invitation.accepted', 'actor_id' => $user->id]);
        $this->get($this->joinUrl($invitation['id']))->assertInertia(fn (Assert $page) => $page->where('invitation.active', false));
    }

    public function test_wrong_email_and_wrong_token_cannot_join(): void
    {
        $invitation = $this->invite();
        $this->actingAs(User::factory()->create())->get($this->joinUrl($invitation['id']))->assertNotFound();
        $this->postJson($this->joinUrl($invitation['id']), ['token' => $invitation['token']])->assertNotFound();
        $this->actingAs(User::factory()->create(['email' => 'recipient@example.test']))->postJson($this->joinUrl($invitation['id']), ['token' => str_repeat('a', 64)])->assertNotFound();
        $this->assertDatabaseCount('tenant_memberships', 1);
    }

    public function test_unverified_recipient_cannot_accept(): void
    {
        $invitation = $this->invite();
        $user = User::factory()->unverified()->create(['email' => 'recipient@example.test']);
        $this->actingAs($user)->post($this->joinUrl($invitation['id']), ['token' => $invitation['token']])->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('tenant_memberships', 1);
    }

    public function test_expired_link_cannot_add_a_member(): void
    {
        $invitation = $this->invite();
        $this->travel(73)->hours();
        $this->actingAs(User::factory()->create(['email' => 'recipient@example.test']))->get($this->joinUrl($invitation['id']))
            ->assertInertia(fn (Assert $page) => $page->where('invitation.active', false));
        $this->postJson($this->joinUrl($invitation['id']), ['token' => $invitation['token']])->assertUnprocessable();
        $this->assertDatabaseCount('tenant_memberships', 1);
    }

    public function test_owner_can_revoke_a_link(): void
    {
        $invitation = $this->invite();
        $this->delete($this->base().'/invitations/'.$invitation['id'])->assertRedirect();
        $this->assertDatabaseHas('workspace_invitations', ['tenant_id' => $this->tenant->id, 'id' => $invitation['id'], 'status' => 'revoked']);
        $this->actingAs(User::factory()->create(['email' => 'recipient@example.test']))->postJson($this->joinUrl($invitation['id']), ['token' => $invitation['token']])->assertUnprocessable();
    }

    public function test_reissuing_revokes_previous_token(): void
    {
        $old = $this->invite();
        $new = $this->invite(role: 'manager');
        $this->assertDatabaseHas('workspace_invitations', ['tenant_id' => $this->tenant->id, 'id' => $old['id'], 'status' => 'revoked']);
        $user = User::factory()->create(['email' => 'recipient@example.test']);
        $this->actingAs($user)->postJson($this->joinUrl($old['id']), ['token' => $old['token']])->assertUnprocessable();
        $this->post($this->joinUrl($new['id']), ['token' => $new['token']])->assertRedirect();
        $this->assertDatabaseHas('tenant_memberships', ['tenant_id' => $this->tenant->id, 'user_id' => $user->id, 'role' => 'manager']);
    }

    public function test_invitation_cannot_upgrade_an_existing_member(): void
    {
        $invitation = $this->invite(role: 'manager');
        $user = User::factory()->create(['email' => 'recipient@example.test']);
        $this->addMember($user, 'auditor');
        $this->actingAs($user)->postJson($this->joinUrl($invitation['id']), ['token' => $invitation['token']])->assertUnprocessable();
        $this->assertDatabaseHas('tenant_memberships', ['tenant_id' => $this->tenant->id, 'user_id' => $user->id, 'role' => 'auditor']);
    }

    public function test_members_are_not_invited_again(): void
    {
        $member = User::factory()->create();
        $this->addMember($member, 'agent');
        $this->actingAs($this->owner)->postJson($this->base().'/invitations', ['email' => $member->email, 'role' => 'manager'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_owner_can_change_role_and_remove_non_owner_without_deleting_user(): void
    {
        $user = User::factory()->create();
        $id = $this->addMember($user, 'agent');
        $this->actingAs($this->owner)->patch($this->base()."/members/{$id}", ['role' => 'auditor', 'current_role' => 'agent'])->assertRedirect();
        $this->assertDatabaseHas('tenant_memberships', ['id' => $id, 'tenant_id' => $this->tenant->id, 'role' => 'auditor']);
        $this->actingAs($user)->post("/workspaces/{$this->tenant->id}/leads", ['name' => 'Blocked', 'source' => 'direct'])->assertForbidden();
        $this->actingAs($this->owner)->delete($this->base()."/members/{$id}", ['current_role' => 'auditor'])->assertRedirect();
        $this->assertDatabaseMissing('tenant_memberships', ['id' => $id, 'tenant_id' => $this->tenant->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->actingAs($user)->get("/workspaces/{$this->tenant->id}")->assertNotFound();
    }

    public function test_owner_cannot_be_demoted_or_removed_even_with_forged_expected_role(): void
    {
        $id = DB::table('tenant_memberships')->where('tenant_id', $this->tenant->id)->where('user_id', $this->owner->id)->value('id');
        $this->actingAs($this->owner)->patchJson($this->base()."/members/{$id}", ['role' => 'agent', 'current_role' => 'agent'])->assertUnprocessable();
        $this->deleteJson($this->base()."/members/{$id}", ['current_role' => 'agent'])->assertUnprocessable();
        $this->assertDatabaseHas('tenant_memberships', ['id' => $id, 'role' => 'owner']);
    }

    public function test_stale_role_changes_are_rejected(): void
    {
        $id = $this->addMember(User::factory()->create(), 'agent');
        $this->actingAs($this->owner)->patch($this->base()."/members/{$id}", ['role' => 'manager', 'current_role' => 'agent'])->assertRedirect();
        $this->patchJson($this->base()."/members/{$id}", ['role' => 'auditor', 'current_role' => 'agent'])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->deleteJson($this->base()."/members/{$id}", ['current_role' => 'agent'])->assertUnprocessable();
    }

    public function test_invitation_and_member_ids_cannot_cross_businesses(): void
    {
        $invitation = $this->invite();
        $other = Tenant::create(['name' => 'Other Business']);
        $otherMember = DB::table('tenant_memberships')->insertGetId(['tenant_id' => $other->id, 'user_id' => $this->owner->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->owner)->patchJson($this->base()."/members/{$otherMember}", ['role' => 'agent', 'current_role' => 'agent'])->assertNotFound();
        $this->deleteJson("/workspaces/{$other->id}/team/invitations/{$invitation['id']}")->assertNotFound();
        $recipient = User::factory()->create(['email' => 'recipient@example.test']);
        $this->actingAs($recipient)->postJson("/join/{$other->id}/{$invitation['id']}", ['token' => $invitation['token']])->assertNotFound();
    }

    public function test_audit_failure_rolls_back_invitation_and_supersession(): void
    {
        $old = $this->invite();
        $this->mock(AuditRecorder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')->andThrow(new \RuntimeException('Simulated audit failure'));
        });
        $this->withoutExceptionHandling();
        try {
            $this->post($this->base().'/invitations', ['email' => 'recipient@example.test', 'role' => 'manager']);
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated audit failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('workspace_invitations', 1);
        $this->assertDatabaseHas('workspace_invitations', ['tenant_id' => $this->tenant->id, 'id' => $old['id'], 'status' => 'pending']);
    }

    public function test_acceptance_audit_failure_rolls_back_membership_and_clears_context(): void
    {
        $invitation = $this->invite();
        $recipient = User::factory()->create(['email' => 'recipient@example.test']);
        $this->mock(AuditRecorder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')->andThrow(new \RuntimeException('Simulated audit failure'));
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($recipient)->post($this->joinUrl($invitation['id']), ['token' => $invitation['token']]);
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated audit failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('tenant_memberships', 1);
        $this->assertDatabaseHas('workspace_invitations', ['tenant_id' => $this->tenant->id, 'id' => $invitation['id'], 'status' => 'pending']);
        $this->expectException(\LogicException::class);
        app(TenantContext::class)->tenant();
    }

    public function test_token_is_not_flashed_back_on_validation_failure(): void
    {
        $invitation = $this->invite();
        $recipient = User::factory()->create(['email' => 'recipient@example.test']);
        $this->actingAs($recipient)->post($this->joinUrl($invitation['id']), ['token' => 'bad-token'])->assertSessionHasErrors('token');
        $this->assertNull(session('_old_input.token'));
    }

    public function test_acceptance_rechecks_the_inviter_is_still_an_owner(): void
    {
        $invitation = $this->invite();
        DB::table('tenant_memberships')->where('tenant_id', $this->tenant->id)->where('user_id', $this->owner->id)->update(['role' => 'manager']);
        $recipient = User::factory()->create(['email' => 'recipient@example.test']);
        $this->actingAs($recipient)->postJson($this->joinUrl($invitation['id']), ['token' => $invitation['token']])->assertForbidden();
        $this->assertDatabaseMissing('tenant_memberships', ['tenant_id' => $this->tenant->id, 'user_id' => $recipient->id]);
        $this->assertDatabaseHas('workspace_invitations', ['tenant_id' => $this->tenant->id, 'id' => $invitation['id'], 'status' => 'pending']);
    }

    public function test_stale_owner_context_cannot_authorize_an_invitation(): void
    {
        $context = app(TenantContext::class);
        $context->set($this->tenant, 'owner');
        DB::table('tenant_memberships')->where('tenant_id', $this->tenant->id)->where('user_id', $this->owner->id)->update(['role' => 'manager']);
        try {
            app(TeamService::class)->invite($this->owner->id, 'new@example.test', 'agent');
            $this->fail('Stored membership must override stale request permission.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        } finally {
            $context->clear();
        }
        $this->assertDatabaseCount('workspace_invitations', 0);
    }

    public function test_audit_failure_rolls_back_role_change_and_membership_removal(): void
    {
        $id = $this->addMember(User::factory()->create(), 'agent');
        $this->mock(AuditRecorder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')->andThrow(new \RuntimeException('Simulated audit failure'));
        });
        $this->withoutExceptionHandling();
        $this->actingAs($this->owner);
        foreach (['patch', 'delete'] as $method) {
            try {
                $this->{$method}($this->base()."/members/{$id}", ['role' => 'auditor', 'current_role' => 'agent']);
                $this->fail('Expected audit failure');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Simulated audit failure', $exception->getMessage());
            }
            $this->assertDatabaseHas('tenant_memberships', ['tenant_id' => $this->tenant->id, 'id' => $id, 'role' => 'agent']);
        }
    }
}
