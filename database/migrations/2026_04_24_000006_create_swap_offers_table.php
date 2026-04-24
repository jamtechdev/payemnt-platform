<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swap_offers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_email');
            $table->string('from_currency_code', 10);
            $table->string('to_currency_code', 10);
            $table->decimal('from_amount', 18, 2);
            $table->decimal('to_amount', 18, 2);
            $table->decimal('admin_share', 8, 2)->nullable();
            $table->decimal('admin_share_amount', 18, 2)->nullable();
            $table->decimal('exchange_rate', 18, 8);
            $table->decimal('base_amount', 18, 2);
            $table->timestamp('expiry_date_time')->nullable();
            $table->string('status', 100)->default('Pending');
            $table->timestamp('date_added')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'status']);
            $table->index(['customer_email', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swap_offers');
    }
};
