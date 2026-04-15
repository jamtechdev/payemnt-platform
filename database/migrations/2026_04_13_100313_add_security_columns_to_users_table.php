<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->boolean('is_active')->default(true)->after('last_login_at');
            $table->integer('login_attempts')->default(0)->after('is_active');
            $table->timestamp('locked_until')->nullable()->after('login_attempts');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->uuid('uuid')->nullable()->unique()->after('slug');
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default('active')->after('phone');
            $table->foreignId('partner_id')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete()->after('partner_id');
            $table->string('first_name')->nullable()->after('product_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->date('cover_start_date')->nullable()->after('last_name');
            $table->integer('cover_duration_months')->nullable()->after('cover_start_date');
            $table->date('cover_end_date')->nullable()->after('cover_duration_months');
            $table->date('customer_since')->nullable()->after('cover_end_date');
            $table->json('submitted_data')->nullable()->after('customer_since');
            $table->json('metadata')->nullable()->after('submitted_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_at',
                'is_active',
                'login_attempts',
                'locked_until',
                'slug',
                'uuid',
                'phone',
                'status',
                'partner_id',
                'product_id',
                'first_name',
                'last_name',
                'cover_start_date',
                'cover_duration_months',
                'cover_end_date',
                'customer_since',
                'submitted_data',
                'metadata',
            ]);
        });
    }
};
