<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wins', function (Blueprint $table) {
            if (Schema::hasColumn('wins', 'win_date')) {
                $table->dropColumn('win_date');
            }
            if (Schema::hasColumn('wins', 'win_time')) {
                $table->dropColumn('win_time');
            }
            $table->timestamp('from_time');
            $table->timestamp('to_time');
            $table->timestamp('draw_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wins', function (Blueprint $table) {
            if (Schema::hasColumn('wins', 'from_time')) {
                $table->dropColumn('from_time');
            }
            if (Schema::hasColumn('wins', 'to_time')) {
                $table->dropColumn('to_time');
            }
            if (Schema::hasColumn('wins', 'draw_time')) {
                $table->dropColumn('draw_time');
            }
        });
    }
};
