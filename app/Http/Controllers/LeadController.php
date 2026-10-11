<?php

namespace App\Http\Controllers;

use App\Modules\Audit\AuditRecorder;
use App\Modules\Configuration\ConfigurationService;
use App\Modules\CRM\Models\Lead;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'won', 'lost'])]]);
        $query = Lead::query()->orderByDesc('created_at')->orderByDesc('id');
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
            });
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $context = app(TenantContext::class);

        return Inertia::render('crm/leads', [
            'workspace' => $context->tenant(), 'leads' => $query->paginate(20)->withQueryString(),
            'filters' => $filters, 'terminology' => app(ConfigurationService::class)->current()['terminology'],
            'canCreate' => $context->allows('leads.create'), 'canUpdate' => $context->allows('leads.update'),
            'canConfigure' => $context->allows('configuration.publish'),
            'canManageTeam' => $context->allows('team.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'email' => ['nullable', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{5,30}$/'],
            'source' => ['required', Rule::in(['direct', 'whatsapp', 'messenger', 'referral', 'website'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        if (! empty($data['phone'])) {
            $data['phone'] = preg_replace('/[ ()-]/', '', $data['phone']);
        }
        $actorId = $request->user()->id;
        DB::transaction(function () use ($actorId, $data): void {
            $lead = Lead::create([...$data, 'created_by' => $actorId]);
            app(AuditRecorder::class)->record($actorId, 'lead.created', 'lead', $lead->id, ['fields' => array_keys($data)]);
        });

        return back();
    }

    public function update(Request $request, string $tenant, string $lead): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'contacted', 'qualified', 'won', 'lost'])]]);
        $actorId = $request->user()->id;
        DB::transaction(function () use ($actorId, $lead, $data): void {
            $record = Lead::whereKey($lead)->lockForUpdate()->firstOrFail();
            $previous = $record->getAttribute('status');
            $record->update($data);
            app(AuditRecorder::class)->record($actorId, 'lead.status_changed', 'lead', $record->id, ['from' => $previous, 'to' => $data['status']]);
        });

        return back();
    }
}
