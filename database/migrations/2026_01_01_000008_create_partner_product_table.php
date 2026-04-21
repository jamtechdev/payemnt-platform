<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_product', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('cover_duration_days_override')->nullable();
            $table->json('rule_overrides')->nullable();
            $table->timestamps();
            $table->unique(['partner_id', 'product_id']);
            $table->index(['product_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_product');
    }
};
