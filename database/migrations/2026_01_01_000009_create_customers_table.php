<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('external_customer_id', 80)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('start_date');
            $table->unsignedInteger('cover_duration_days');
            $table->date('cover_end_date')->index();
            $table->date('customer_since')->index();
            $table->timestamp('last_payment_date')->nullable()->index();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->index();
            $table->json('customer_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['partner_id', 'product_id', 'created_at']);
            $table->index(['partner_id', 'status', 'created_at']);
            $table->index(['product_id', 'status', 'created_at']);
            $table->index(['email', 'phone']);
            $table->unique(['partner_id', 'product_id', 'external_customer_id'], 'cust_partner_product_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
