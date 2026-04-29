<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_apis', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->text('url');
            $table->string('status', 50)->default('Active');
            $table->integer('partner_id')->nullable();
            $table->string('partner_code', 100)->nullable();
            $table->tinyInteger('from_platform')->default(0);
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_apis');
    }
};
