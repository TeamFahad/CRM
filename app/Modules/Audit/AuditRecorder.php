<?php

namespace App\Modules\Audit;

use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditRecorder
{
    /** @param array<string, mixed> $metadata */
    public function record(int $actorId, string $action, string $type, string $id, array $metadata = []): void
    {
        DB::table('audit_logs')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => app(TenantContext::class)->tenant()->id,
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => $id,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
