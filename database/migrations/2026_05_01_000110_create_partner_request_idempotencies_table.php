<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_request_idempotencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('idempotency_key', 120);
            $table->string('endpoint', 180);
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('status_code')->default(200);
            $table->json('response_payload');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['partner_id', 'endpoint', 'idempotency_key'], 'partner_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_request_idempotencies');
    }
};
