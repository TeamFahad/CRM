<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('industry')->default('service');
            $table->string('locale')->default('en');
            $table->string('timezone')->default('Asia/Dhaka');
            $table->string('currency', 3)->default('BDT');
            $table->timestamps();
        });
        Schema::create('tenant_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
            $table->index('user_id');
        });
        Schema::create('configuration_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('settings');
            $table->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at');
            $table->unique(['tenant_id', 'version']);
            $table->index('published_by');
        });
        Schema::create('leads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('source')->default('direct');
            $table->string('status')->default('new');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('created_by');
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->string('subject_id');
            $table->json('metadata');
            $table->timestamp('created_at');
            $table->index(['tenant_id', 'created_at']);
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('configuration_versions');
        Schema::dropIfExists('tenant_memberships');
        Schema::dropIfExists('tenants');
    }
};
