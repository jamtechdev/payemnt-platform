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
            $table->string('customer_email');
            $table->string('product_code');
            $table->string('product_type');
            $table->string('cover_duration');
            $table->date('cover_start_date');
            $table->date('cover_end_date');
            $table->string('payment_status');
            $table->string('transaction_number')->nullable();
            $table->timestamp('date_added')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'customer_email']);
            $table->index(['partner_id', 'transaction_number']);
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

        Schema::create('system_currencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 10);
            $table->string('symbol', 10);
            $table->decimal('margin', 5, 2);
            $table->decimal('admin_rate', 10, 2);
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->index(['partner_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_currencies');
        Schema::dropIfExists('products_purchases_claims');
        Schema::dropIfExists('products_purchases');
        Schema::dropIfExists('referral_usages');
    }
};
