<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('webhook_logs');

        Schema::table('partners', function (Blueprint $table): void {
            foreach (['webhook_secret', 'webhook_url'] as $column) {
                if (Schema::hasColumn('partners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            if (! Schema::hasColumn('partners', 'webhook_url')) {
                $table->string('webhook_url')->nullable()->after('website_url');
            }

            if (! Schema::hasColumn('partners', 'webhook_secret')) {
                $table->string('webhook_secret', 120)->nullable()->after('webhook_url');
            }
        });

        if (! Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->string('event', 60);
                $table->string('target_url');
                $table->json('payload');
                $table->unsignedSmallInteger('status_code')->nullable()->index();
                $table->unsignedTinyInteger('attempt')->default(1);
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->index();
                $table->text('response_body')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('next_retry_at')->nullable()->index();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }
};
