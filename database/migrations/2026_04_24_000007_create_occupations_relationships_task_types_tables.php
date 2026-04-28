<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });

        Schema::create('relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });

        Schema::create('task_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_types');
        Schema::dropIfExists('relationships');
        Schema::dropIfExists('occupations');
    }
};
