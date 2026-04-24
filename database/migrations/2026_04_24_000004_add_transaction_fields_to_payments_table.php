<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('transaction_number')->nullable()->unique()->after('uuid');
            $table->string('product_type')->nullable()->after('product_id');
            $table->string('cover_duration')->nullable()->after('product_type');
            $table->date('cover_start_date')->nullable()->after('cover_duration');
            $table->date('cover_end_date')->nullable()->after('cover_start_date');
            $table->string('payment_message')->nullable()->after('status');
            $table->string('stripe_payment_intent')->nullable()->after('payment_message');
            $table->string('stripe_payment_status')->nullable()->after('stripe_payment_intent');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'transaction_number',
                'product_type',
                'cover_duration',
                'cover_start_date',
                'cover_end_date',
                'payment_message',
                'stripe_payment_intent',
                'stripe_payment_status',
            ]);
        });
    }
};
