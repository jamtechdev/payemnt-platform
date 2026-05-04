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
            if (! Schema::hasColumn('partners', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('partners', 'website_url')) {
                $table->string('website_url')->nullable()->after('company_name');
            }
            if (! Schema::hasColumn('partners', 'notes')) {
                $table->text('notes')->nullable()->after('settings');
            }
            if (! Schema::hasColumn('partners', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'validation_rules')) {
                $table->json('validation_rules')->nullable()->after('api_schema');
            }
            if (! Schema::hasColumn('products', 'api_endpoint')) {
                $table->string('api_endpoint')->nullable()->after('validation_rules');
            }
            if (! Schema::hasColumn('products', 'api_documentation')) {
                $table->longText('api_documentation')->nullable()->after('api_endpoint');
            }
            if (! Schema::hasColumn('products', 'terms_and_conditions')) {
                $table->longText('terms_and_conditions')->nullable()->after('api_documentation');
            }
            if (! Schema::hasColumn('products', 'features')) {
                $table->json('features')->nullable()->after('terms_and_conditions');
            }
            if (! Schema::hasColumn('products', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('features')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'phone')) {
                $table->string('phone', 30)->nullable()->after('customer_email');
            }
            if (! Schema::hasColumn('payments', 'policy_number')) {
                $table->string('policy_number', 80)->nullable()->after('transaction_number');
            }
            if (! Schema::hasColumn('payments', 'kyc_data')) {
                $table->json('kyc_data')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('payments', 'submitted_payload')) {
                $table->json('submitted_payload')->nullable()->after('kyc_data');
            }
            if (! Schema::hasColumn('payments', 'api_response')) {
                $table->json('api_response')->nullable()->after('submitted_payload');
            }
        });

        if (! Schema::hasTable('transaction_logs')) {
            Schema::create('transaction_logs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
                $table->string('event', 80);
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->text('error_message')->nullable();
                $table->string('source', 40)->default('api');
                $table->timestamp('occurred_at')->index();
                $table->timestamps();
                $table->index(['payment_id', 'event']);
            });
        }

        if (! Schema::hasTable('api_logs')) {
            Schema::create('api_logs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
                $table->string('method', 12);
                $table->string('path');
                $table->string('endpoint_group', 60)->nullable();
                $table->json('request_body')->nullable();
                $table->json('response_body')->nullable();
                $table->unsignedSmallInteger('status_code')->index();
                $table->unsignedInteger('response_time_ms')->default(0)->index();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->string('source', 80)->nullable();
                $table->string('correlation_id', 64)->nullable()->index();
                $table->timestamp('requested_at')->index();
                $table->timestamps();
                $table->index(['partner_id', 'requested_at']);
            });
        }

        if (! Schema::hasTable('analytics_daily_rollups')) {
            Schema::create('analytics_daily_rollups', function (Blueprint $table): void {
                $table->id();
                $table->date('period_date')->index();
                $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->unsignedInteger('transactions_total')->default(0);
                $table->unsignedInteger('transactions_success')->default(0);
                $table->unsignedInteger('transactions_failed')->default(0);
                $table->decimal('estimated_revenue', 14, 2)->default(0);
                $table->timestamps();
                $table->unique(['period_date', 'partner_id', 'product_id'], 'analytics_daily_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_rollups');
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('transaction_logs');

        Schema::table('products', function (Blueprint $table): void {
            foreach (['created_by', 'features', 'terms_and_conditions', 'api_documentation', 'api_endpoint', 'validation_rules', 'category'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('partners', function (Blueprint $table): void {
            foreach (['created_by', 'notes', 'website_url', 'company_name'] as $column) {
                if (Schema::hasColumn('partners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            foreach (['api_response', 'submitted_payload', 'kyc_data', 'policy_number', 'phone'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
