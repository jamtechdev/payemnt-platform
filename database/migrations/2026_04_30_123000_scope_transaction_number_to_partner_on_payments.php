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
            $table->dropUnique('payments_transaction_number_unique');
            $table->unique(['partner_id', 'transaction_number'], 'payments_partner_transaction_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_partner_transaction_unique');
            $table->unique('transaction_number');
        });
    }
};
