<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table): void {
            $table->id('faq_id');
            $table->string('faq_code', 100)->nullable();
            $table->text('question');
            $table->text('answer');
            $table->string('partner_code', 100)->nullable();
            $table->string('status', 50)->default('active');
            $table->tinyInteger('from_platform')->default(0);
            $table->integer('partner_id')->nullable();
            $table->timestamps();

            $table->index(['faq_code', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
