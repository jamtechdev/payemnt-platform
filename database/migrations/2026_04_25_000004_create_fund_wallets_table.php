<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('customer_email');
            $table->string('bank_name');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('status')->default('Pending');
            $table->timestamp('date_added')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'customer_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_wallets');
    }
};
