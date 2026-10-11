<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->string('email', 254);
            $table->string('role', 30);
            $table->string('token_hash', 64)->unique();
            $table->string('status', 20)->default('pending');
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->index('invited_by');
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->index('accepted_by');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            $table->index(['tenant_id', 'email', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_invitations');
    }
};
