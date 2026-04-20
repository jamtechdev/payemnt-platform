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
        Schema::table('partner_products', function (Blueprint $table): void {
            $table->decimal('partner_price', 10, 2)->nullable()->after('status');
            $table->char('partner_currency', 3)->nullable()->after('partner_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_products', function (Blueprint $table): void {
            $table->dropColumn(['partner_price', 'partner_currency']);
        });
    }
};
