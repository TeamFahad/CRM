<?php

namespace App\Modules\Tenancy\Concerns;

use App\Modules\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $builder->where($builder->getModel()->qualifyColumn('tenant_id'), app(TenantContext::class)->tenant()->id);
        });

        static::saving(function (Model $model): void {
            $tenantId = app(TenantContext::class)->tenant()->id;
            if ($model->getAttribute('tenant_id') !== null && $model->getAttribute('tenant_id') !== $tenantId) {
                throw new LogicException('Cross-tenant writes are forbidden.');
            }
            $model->setAttribute('tenant_id', $tenantId);
            if ($model->exists && $model->getRawOriginal('tenant_id') !== $tenantId) {
                throw new LogicException('Tenant ownership cannot be reassigned.');
            }
        });
    }
}
