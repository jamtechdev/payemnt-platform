<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('partner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('cover_start_date')->nullable();
            $table->integer('cover_duration_months')->nullable();
            $table->date('cover_end_date')->nullable();
            $table->date('customer_since')->nullable();
            $table->json('submitted_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['partner_user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
