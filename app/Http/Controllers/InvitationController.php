<?php

namespace App\Http\Controllers;

use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $tenant, string $invitation, TeamService $service): Response
    {
        $workspace = Tenant::findOrFail($tenant);

        return Inertia::render('team/accept', [
            'invitation' => $service->preview($workspace, $invitation, $request->user()),
            'acceptUrl' => route('invitations.accept', ['tenant' => $tenant, 'invitation' => $invitation], absolute: false),
        ]);
    }

    public function accept(Request $request, string $tenant, string $invitation, TeamService $service): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/']]);
        $service->accept(Tenant::findOrFail($tenant), $invitation, $data['token'], $request->user());

        return to_route('workspaces.show', ['tenant' => $tenant]);
    }
}
