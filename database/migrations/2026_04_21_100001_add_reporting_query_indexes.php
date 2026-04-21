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
            $table->index(['status', 'paid_at'], 'payments_status_paid_at_report_idx');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->index(['partner_id', 'customer_since'], 'customers_partner_customer_since_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_status_paid_at_report_idx');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_partner_customer_since_idx');
        });
    }
};
