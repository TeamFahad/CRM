<?php

namespace App\Http\Middleware;

use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspace
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->route('tenant');
        abort_unless(is_string($id), 404);
        $tenant = Tenant::findOrFail($id);
        $membership = DB::table('tenant_memberships')->where('tenant_id', $tenant->id)->where('user_id', $request->user()?->id)->first();
        abort_unless($membership !== null, 404);
        $this->context->set($tenant, $membership->role);

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
