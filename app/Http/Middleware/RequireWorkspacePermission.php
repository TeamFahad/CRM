<?php

namespace App\Http\Middleware;

use App\Modules\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWorkspacePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(app(TenantContext::class)->allows($permission), 403);

        return $next($request);
    }
}
