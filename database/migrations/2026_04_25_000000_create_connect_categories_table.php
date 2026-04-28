<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connect_categories', function (Blueprint $table): void {
            $table->id('connect_categories_id');
            $table->string('category_code', 100)->nullable();
            $table->string('name', 255);
            $table->string('icon_url', 500)->nullable();
            $table->string('partner_code', 100)->nullable();
            $table->string('status', 50)->default('active');
            $table->tinyInteger('from_platform')->default(0);
            $table->integer('partner_id')->nullable();
            $table->timestamps();

            $table->index(['category_code', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_categories');
    }
};
