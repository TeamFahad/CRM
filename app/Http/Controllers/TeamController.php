<?php

namespace App\Http\Controllers;

use App\Modules\Tenancy\TeamService;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $context = app(TenantContext::class);
        $tenant = $context->tenant();
        $encryptedLink = $request->session()->pull('team_invitation_link');
        if (is_string($encryptedLink)) {
            Inertia::flash('invitationLink', Crypt::decryptString($encryptedLink));
        }

        return Inertia::render('team/index', [
            'workspace' => $tenant, 'canManageTeam' => true,
            'members' => DB::table('tenant_memberships')->join('users', 'users.id', '=', 'tenant_memberships.user_id')
                ->where('tenant_memberships.tenant_id', $tenant->id)->select('tenant_memberships.id', 'tenant_memberships.role', 'users.name', 'users.email')
                ->orderBy('tenant_memberships.id')->paginate(50, pageName: 'members_page')->withQueryString(),
            'invitations' => DB::table('workspace_invitations')->where('tenant_id', $tenant->id)
                ->select('id', 'email', 'role', 'status', 'expires_at')->orderByDesc('created_at')->orderByDesc('id')
                ->paginate(20, pageName: 'invitations_page')->withQueryString(),
            'roles' => TeamService::ASSIGNABLE_ROLES,
        ]);
    }

    public function invite(Request $request, TeamService $service): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:254'], 'role' => ['required', Rule::in(TeamService::ASSIGNABLE_ROLES)]]);
        $invitation = $service->invite($request->user()->id, $data['email'], $data['role']);
        $link = route('invitations.show', ['tenant' => app(TenantContext::class)->tenant()->id, 'invitation' => $invitation['id']]).'#token='.$invitation['token'];
        $request->session()->flash('team_invitation_link', Crypt::encryptString($link));

        return to_route('team.index', ['tenant' => app(TenantContext::class)->tenant()->id]);
    }

    public function revoke(Request $request, string $tenant, string $invitation, TeamService $service): RedirectResponse
    {
        $service->revoke($request->user()->id, $invitation);

        return back();
    }

    public function update(Request $request, string $tenant, int $member, TeamService $service): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(TeamService::ASSIGNABLE_ROLES)], 'current_role' => ['required', Rule::in(TeamService::ASSIGNABLE_ROLES)]]);
        $service->changeRole($request->user()->id, $member, $data['role'], $data['current_role']);

        return back();
    }

    public function destroy(Request $request, string $tenant, int $member, TeamService $service): RedirectResponse
    {
        $data = $request->validate(['current_role' => ['required', Rule::in(TeamService::ASSIGNABLE_ROLES)]]);
        $service->remove($request->user()->id, $member, $data['current_role']);

        return back();
    }
}
