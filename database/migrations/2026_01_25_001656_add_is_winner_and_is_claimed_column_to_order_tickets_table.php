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
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->integer('is_winner')->default(0)->comment('0 = not, 1= win')->after('selected_play_types');
            $table->integer('is_claimed')->default(0)->comment('0 = not claimed, 1 = claimed')->after('is_winner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->dropColumn(['is_winner', 'is_claimed']);
        });
    }
};
