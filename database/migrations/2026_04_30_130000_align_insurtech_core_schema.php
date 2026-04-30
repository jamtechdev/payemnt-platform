<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'product_name')) {
                $table->string('product_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('products', 'guide_price')) {
                $table->decimal('guide_price', 12, 2)->nullable()->after('base_price');
            }
            if (! Schema::hasColumn('products', 'api_schema')) {
                $table->json('api_schema')->nullable()->after('settings');
            }
        });

        DB::table('products')
            ->whereNull('product_name')
            ->update(['product_name' => DB::raw('name')]);

        DB::table('products')
            ->whereNull('guide_price')
            ->whereNotNull('price')
            ->update(['guide_price' => DB::raw('price')]);

        Schema::table('partners', function (Blueprint $table): void {
            if (! Schema::hasColumn('partners', 'partner_name')) {
                $table->string('partner_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('partners', 'email')) {
                $table->string('email')->nullable()->after('contact_email');
            }
            if (! Schema::hasColumn('partners', 'phone')) {
                $table->string('phone', 20)->nullable()->after('contact_phone');
            }
        });

        DB::table('partners')
            ->whereNull('partner_name')
            ->update(['partner_name' => DB::raw('name')]);

        DB::table('partners')
            ->whereNull('email')
            ->whereNotNull('contact_email')
            ->update(['email' => DB::raw('contact_email')]);

        DB::table('partners')
            ->whereNull('phone')
            ->whereNotNull('contact_phone')
            ->update(['phone' => DB::raw('contact_phone')]);

        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('transaction_number');
            }
            if (! Schema::hasColumn('payments', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_name');
            }
            if (! Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('payment_message');
            }
            $table->index(['partner_id', 'status', 'paid_at'], 'payments_partner_status_paid_at_idx');
        });

        DB::table('payments')
            ->select(['payments.id', 'customers.first_name', 'customers.last_name', 'customers.email'])
            ->join('customers', 'customers.id', '=', 'payments.customer_id')
            ->orderBy('payments.id')
            ->chunkById(250, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('payments')
                        ->where('id', $row->id)
                        ->update([
                            'customer_name' => trim(sprintf('%s %s', (string) $row->first_name, (string) $row->last_name)),
                            'customer_email' => $row->email,
                        ]);
                }
            }, 'payments.id');
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
            if (Schema::hasColumn('payments', 'customer_email')) {
                $table->dropColumn('customer_email');
            }
            if (Schema::hasColumn('payments', 'notes')) {
                $table->dropColumn('notes');
            }
            $table->dropIndex('payments_partner_status_paid_at_idx');
        });

        Schema::table('partners', function (Blueprint $table): void {
            if (Schema::hasColumn('partners', 'partner_name')) {
                $table->dropColumn('partner_name');
            }
            if (Schema::hasColumn('partners', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('partners', 'phone')) {
                $table->dropColumn('phone');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'product_name')) {
                $table->dropColumn('product_name');
            }
            if (Schema::hasColumn('products', 'guide_price')) {
                $table->dropColumn('guide_price');
            }
            if (Schema::hasColumn('products', 'api_schema')) {
                $table->dropColumn('api_schema');
            }
        });
    }
};
