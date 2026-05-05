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
            // Drop old string column, add FK to currencies
            $table->dropColumn('currency_code');
            $table->foreignId('currency_id')->nullable()->after('is_enabled')->constrained('currencies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('partner_product', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('currency_id');
            $table->string('currency_code', 10)->nullable()->after('is_enabled');
        });
    }
};
