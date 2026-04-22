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
            // Drop old unique constraints
            $table->dropUnique(['slug']);
            $table->dropUnique(['partner_code']);
        });

        // Add new unique constraints that include deleted_at
        // So soft-deleted records don't block new ones with same slug/partner_code
        DB::statement('ALTER TABLE partners ADD UNIQUE unique_slug_soft_delete (slug, deleted_at)');
        DB::statement('ALTER TABLE partners ADD UNIQUE unique_partner_code_soft_delete (partner_code, deleted_at)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE partners DROP INDEX unique_slug_soft_delete');
        DB::statement('ALTER TABLE partners DROP INDEX unique_partner_code_soft_delete');

        Schema::table('partners', function (Blueprint $table): void {
            $table->unique('slug');
            $table->unique('partner_code');
        });
    }
};
