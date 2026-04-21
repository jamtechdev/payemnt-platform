<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            if (! Schema::hasColumn('partners', 'api_key')) {
                $table->string('api_key')->nullable()->after('partner_code');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'country')) {
                $table->string('country', 2)->nullable()->after('product_code')->index();
            }
            if (! Schema::hasColumn('products', 'cover_duration_type')) {
                $table->enum('cover_duration_type', ['monthly', 'annual', 'custom'])
                    ->default('custom')
                    ->after('cover_duration_mode')
                    ->index();
            }
        });

        Schema::table('partner_product', function (Blueprint $table): void {
            if (! Schema::hasColumn('partner_product', 'partner_price')) {
                $table->decimal('partner_price', 12, 2)->nullable()->after('is_enabled');
            }
            if (! Schema::hasColumn('partner_product', 'partner_currency')) {
                $table->char('partner_currency', 3)->nullable()->after('partner_price');
            }
        });

        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'customer_code')) {
                $table->string('customer_code', 40)->nullable()->after('uuid')->unique();
            }
            if (! Schema::hasColumn('customers', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('last_name')->index();
            }
            if (! Schema::hasColumn('customers', 'age')) {
                $table->unsignedSmallInteger('age')->nullable()->after('date_of_birth');
            }
            if (! Schema::hasColumn('customers', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('age');
            }
            if (! Schema::hasColumn('customers', 'address')) {
                $table->string('address')->nullable()->after('gender');
            }
            if (! Schema::hasColumn('customers', 'cover_start_date')) {
                $table->date('cover_start_date')->nullable()->after('address')->index();
            }
            if (! Schema::hasColumn('customers', 'cover_duration')) {
                $table->enum('cover_duration', ['monthly', 'annual'])->nullable()->after('cover_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_code',
                'date_of_birth',
                'age',
                'gender',
                'address',
                'cover_start_date',
                'cover_duration',
            ]);
        });

        Schema::table('partner_product', function (Blueprint $table): void {
            $table->dropColumn(['partner_price', 'partner_currency']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['country', 'cover_duration_type']);
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn('api_key');
        });
    }
};
