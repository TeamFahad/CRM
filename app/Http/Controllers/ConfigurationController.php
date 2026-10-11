<?php

namespace App\Http\Controllers;

use App\Modules\Configuration\ConfigurationService;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    public function index(ConfigurationService $service): Response
    {
        return Inertia::render('configuration/index', ['workspace' => app(TenantContext::class)->tenant(), 'configuration' => $service->current(), 'canManageTeam' => app(TenantContext::class)->allows('team.manage')]);
    }

    public function store(Request $request, ConfigurationService $service): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
            'terminology' => ['required', 'array:lead,customer'],
            'terminology.lead' => ['required', 'string', 'max:40'],
            'terminology.customer' => ['required', 'string', 'max:40'],
        ]);
        $service->publish($request->user()->id, $data['version'], $data['terminology']);

        return back();
    }
}
