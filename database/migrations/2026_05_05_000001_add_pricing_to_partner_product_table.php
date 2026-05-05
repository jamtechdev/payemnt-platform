<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_product', function (Blueprint $table): void {
            $table->string('currency_code', 10)->nullable()->after('is_enabled');
            $table->decimal('base_price', 15, 2)->nullable()->after('currency_code');
            $table->decimal('guide_price', 15, 2)->nullable()->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('partner_product', function (Blueprint $table): void {
            $table->dropColumn(['currency_code', 'base_price', 'guide_price']);
        });
    }
};
