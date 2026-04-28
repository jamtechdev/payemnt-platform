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
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->unsignedBigInteger('swap_offers_requests_id');
            $table->string('from_user_name');
            $table->string('from_user_email');
            $table->string('to_user_name');
            $table->string('to_user_email');
            $table->string('from_currency_name');
            $table->string('from_currency_code', 10);
            $table->string('to_currency_name');
            $table->string('to_currency_code', 10);
            $table->decimal('from_amount', 15, 2);
            $table->decimal('to_amount', 15, 2);
            $table->decimal('admin_share', 5, 2);
            $table->decimal('admin_share_amount', 15, 2);
            $table->decimal('base_amount', 15, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->timestamps();

            $table->index(['from_user_email', 'to_user_email']);
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
