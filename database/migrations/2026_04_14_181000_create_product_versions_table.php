<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedInteger('version_number');
            $table->json('snapshot');
            $table->timestamps();
            $table->index(['product_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_versions');
    }
};
