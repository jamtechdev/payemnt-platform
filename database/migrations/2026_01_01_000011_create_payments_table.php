<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->timestamp('paid_at')->index();
            $table->string('transaction_reference')->nullable()->index();
            $table->enum('status', ['success', 'failed', 'pending', 'refunded'])->default('success')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['partner_id', 'product_id', 'paid_at']);
            $table->index(['customer_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
