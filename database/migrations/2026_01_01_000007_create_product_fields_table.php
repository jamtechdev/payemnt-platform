<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('field_key');
            $table->string('label');
            $table->enum('field_type', ['text', 'number', 'date', 'datetime', 'dropdown', 'boolean', 'email', 'phone']);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->json('options')->nullable();
            $table->string('validation_rule')->nullable();
            $table->json('default_value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'field_key']);
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_fields');
    }
};
