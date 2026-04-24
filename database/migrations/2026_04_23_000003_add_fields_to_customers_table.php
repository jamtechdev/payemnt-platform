<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // All fields already included in create_customers_table migration
    }

    public function down(): void
    {
        // Nothing to rollback
    }
};
