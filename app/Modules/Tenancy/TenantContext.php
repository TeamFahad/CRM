<?php

namespace App\Modules\Tenancy;

use App\Modules\Tenancy\Models\Tenant;
use LogicException;

class TenantContext
{
    private ?Tenant $tenant = null;

    private ?string $role = null;

    public function set(Tenant $tenant, string $role): void
    {
        $this->tenant = $tenant;
        $this->role = $role;
    }

    public function tenant(): Tenant
    {
        return $this->tenant ?? throw new LogicException('Tenant context is required.');
    }

    public function allows(string $permission): bool
    {
        $permissions = config('platform.roles.'.$this->role, []);

        return in_array($permission, $permissions, true);
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->role = null;
    }
}
