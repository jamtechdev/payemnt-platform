<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('audit_logs', 'actor_user_id')) {
                $table->unsignedBigInteger('actor_user_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('audit_logs', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('actor_user_id');
            }

            if (! Schema::hasColumn('audit_logs', 'entity_type')) {
                $table->string('entity_type')->default('system')->after('action');
            }

            if (! Schema::hasColumn('audit_logs', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            }

            if (! Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('entity_id');
            }

            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }

            if (! Schema::hasColumn('audit_logs', 'changes')) {
                $table->json('changes')->nullable()->after('user_agent');
            }

            if (! Schema::hasColumn('audit_logs', 'occurred_at')) {
                $table->timestamp('occurred_at')->nullable()->after('changes');
            }

            if (! Schema::hasColumn('audit_logs', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('occurred_at');
            }

            if (! Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        // Intentionally no-op: this migration is a compatibility patch for existing environments.
    }
};

