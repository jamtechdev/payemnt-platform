<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_meta', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('meta_key');
            $table->json('meta_value')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'meta_key']);
            $table->index(['meta_key', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_meta');
    }
};
