<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed common currencies
        DB::table('currencies')->insert([
            ['code' => 'USD', 'name' => 'US Dollar',         'symbol' => '$',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EUR', 'name' => 'Euro',               'symbol' => '€',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GBP', 'name' => 'British Pound',      'symbol' => '£',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'INR', 'name' => 'Indian Rupee',       'symbol' => '₹',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'NGN', 'name' => 'Nigerian Naira',     'symbol' => '₦',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'KES', 'name' => 'Kenyan Shilling',    'symbol' => 'KSh','is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GHS', 'name' => 'Ghanaian Cedi',      'symbol' => '₵',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'AED', 'name' => 'UAE Dirham',         'symbol' => 'د.إ','is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CAD', 'name' => 'Canadian Dollar',    'symbol' => 'C$', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'AUD', 'name' => 'Australian Dollar',  'symbol' => 'A$', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'JPY', 'name' => 'Japanese Yen',       'symbol' => '¥',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
