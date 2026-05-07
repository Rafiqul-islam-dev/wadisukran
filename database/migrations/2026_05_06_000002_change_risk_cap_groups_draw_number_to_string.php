<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('risk_cap_groups') || ! Schema::hasColumn('risk_cap_groups', 'draw_number')) {
            return;
        }

        // Orders.draw_number is varchar/string in this project, so Risk Cap group
        // must also support values like LOCK-PRIZE-20260507.
        try { DB::statement('ALTER TABLE risk_cap_groups DROP INDEX risk_cap_group_unique'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE risk_cap_groups DROP INDEX risk_cap_group_lookup'); } catch (\Throwable $e) {}

        DB::statement('ALTER TABLE risk_cap_groups MODIFY draw_number VARCHAR(255) NULL');

        try { DB::statement('ALTER TABLE risk_cap_groups ADD UNIQUE risk_cap_group_unique (product_id, draw_number, user_id)'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE risk_cap_groups ADD INDEX risk_cap_group_lookup (status, product_id, draw_number, user_id)'); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        if (! Schema::hasTable('risk_cap_groups') || ! Schema::hasColumn('risk_cap_groups', 'draw_number')) {
            return;
        }

        try { DB::statement('ALTER TABLE risk_cap_groups DROP INDEX risk_cap_group_unique'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE risk_cap_groups DROP INDEX risk_cap_group_lookup'); } catch (\Throwable $e) {}

        // Down conversion may fail if string draw numbers exist; that is safer than silently losing data.
        DB::statement('ALTER TABLE risk_cap_groups MODIFY draw_number BIGINT UNSIGNED NULL');

        try { DB::statement('ALTER TABLE risk_cap_groups ADD UNIQUE risk_cap_group_unique (product_id, draw_number, user_id)'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE risk_cap_groups ADD INDEX risk_cap_group_lookup (status, product_id, draw_number, user_id)'); } catch (\Throwable $e) {}
    }
};
