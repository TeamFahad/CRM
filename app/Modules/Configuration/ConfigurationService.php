<?php

namespace App\Modules\Configuration;

use App\Modules\Audit\AuditRecorder;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConfigurationService
{
    /** @return array{version: int, terminology: array{lead: string, customer: string}} */
    public function current(): array
    {
        $row = DB::table('configuration_versions')->where('tenant_id', app(TenantContext::class)->tenant()->id)->orderByDesc('version')->first();
        $settings = $row ? json_decode($row->settings, true, flags: JSON_THROW_ON_ERROR) : [];

        return [
            'version' => $row ? (int) $row->version : 0,
            'terminology' => $settings['terminology'] ?? ['lead' => 'Lead', 'customer' => 'Customer'],
        ];
    }

    /** @param array{lead: string, customer: string} $terminology */
    public function publish(int $actorId, int $expectedVersion, array $terminology): void
    {
        DB::transaction(function () use ($actorId, $expectedVersion, $terminology): void {
            $tenantId = app(TenantContext::class)->tenant()->id;
            DB::table('tenants')->where('id', $tenantId)->lockForUpdate()->first();
            $current = $this->current();
            if ($current['version'] !== $expectedVersion) {
                throw ValidationException::withMessages(['version' => 'Configuration changed. Reload before publishing.']);
            }
            $id = (string) Str::ulid();
            DB::table('configuration_versions')->insert([
                'id' => $id,
                'tenant_id' => $tenantId,
                'version' => $expectedVersion + 1,
                'settings' => json_encode(['terminology' => $terminology], JSON_THROW_ON_ERROR),
                'published_by' => $actorId,
                'published_at' => now(),
            ]);
            app(AuditRecorder::class)->record($actorId, 'configuration.published', 'configuration', $id, ['version' => $expectedVersion + 1]);
        });
    }
}
