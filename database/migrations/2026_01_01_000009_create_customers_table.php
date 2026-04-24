<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('customer_code', 80)->nullable();
            $table->foreignId('platform_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('external_customer_id', 80)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('location')->nullable();
            $table->string('valid_document')->nullable();
            $table->string('id_front_image')->nullable();
            $table->string('id_back_image')->nullable();
            $table->string('profile_pic')->nullable();
            $table->date('start_date')->nullable();
            $table->unsignedInteger('cover_duration_days')->nullable();
            $table->date('cover_end_date')->nullable()->index();
            $table->date('customer_since')->nullable()->index();
            $table->timestamp('last_payment_date')->nullable()->index();
            $table->enum('status', ['Pending', 'Active', 'Inactive', 'Deleted'])->default('Pending')->index();
            $table->json('customer_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['partner_id', 'product_id', 'created_at']);
            $table->index(['partner_id', 'status', 'created_at']);
            $table->index(['product_id', 'status', 'created_at']);
            $table->index(['email', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
