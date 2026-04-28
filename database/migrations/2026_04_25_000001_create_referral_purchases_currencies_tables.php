<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('referrer_email');
            $table->string('used_by_email');
            $table->string('refer_code');
            $table->timestamp('date_used')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'refer_code']);
        });

        Schema::create('products_purchases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('swap_offers_requests_id');
            $table->unsignedBigInteger('from_users_customers_id');
            $table->unsignedBigInteger('to_users_customers_id');
            $table->unsignedBigInteger('from_system_currencies_id');
            $table->unsignedBigInteger('to_system_currencies_id');
            $table->decimal('from_amount', 15, 2);
            $table->decimal('to_amount', 15, 2);
            $table->decimal('admin_share', 5, 2);
            $table->decimal('admin_share_amount', 15, 2);
            $table->unsignedBigInteger('system_currencies_id');
            $table->decimal('base_amount', 15, 2);
            $table->unsignedBigInteger('payment_method_id');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('products_purchases_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('customer_email');
            $table->string('product_code');
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('acknowledged')->default('No');
            $table->timestamp('date_added')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'customer_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products_purchases_claims');
        Schema::dropIfExists('products_purchases');
        Schema::dropIfExists('referral_usages');
    }
};
