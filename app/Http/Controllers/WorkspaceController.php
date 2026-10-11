<?php

namespace App\Http\Controllers;

use App\Modules\CRM\Models\Lead;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('dashboard', [
            'workspaces' => DB::table('tenants')->join('tenant_memberships', 'tenants.id', '=', 'tenant_memberships.tenant_id')->where('user_id', $request->user()?->id)->select('tenants.*', 'tenant_memberships.role')->orderBy('tenants.name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'industry' => ['required', 'in:service,clinic,retail,distribution,manufacturing,education'],
            'locale' => ['required', 'in:en,bn'],
        ]);
        $tenant = DB::transaction(function () use ($request, $data): Tenant {
            $tenant = Tenant::create($data);
            DB::table('tenant_memberships')->insert([
                'tenant_id' => $tenant->id, 'user_id' => $request->user()?->id,
                'role' => 'owner', 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $tenant;
        });

        return to_route('workspaces.show', ['tenant' => $tenant->id]);
    }

    public function show(): Response
    {
        $context = app(TenantContext::class);

        return Inertia::render('workspaces/show', [
            'workspace' => $context->tenant(),
            'metrics' => ['leads' => Lead::count(), 'newLeads' => Lead::where('status', 'new')->count(), 'wonLeads' => Lead::where('status', 'won')->count()],
            'canConfigure' => $context->allows('configuration.publish'),
            'modules' => config('platform.modules'),
        ]);
    }
}
