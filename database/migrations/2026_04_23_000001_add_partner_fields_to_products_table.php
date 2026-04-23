<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('partner_id')->nullable()->after('id');
            $table->string('partner_code', 40)->nullable()->after('partner_id');
            $table->decimal('price', 12, 2)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['partner_id', 'partner_code', 'price']);
        });
    }
};
