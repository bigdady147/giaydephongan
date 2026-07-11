<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE categories MODIFY seo_description TEXT NULL');
            DB::statement('ALTER TABLE products MODIFY seo_description TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE categories MODIFY seo_description VARCHAR(255) NULL');
            DB::statement('ALTER TABLE products MODIFY seo_description VARCHAR(255) NULL');
        }
    }
};
