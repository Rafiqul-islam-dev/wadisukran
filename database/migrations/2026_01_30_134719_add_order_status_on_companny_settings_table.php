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
        Schema::table('companny_settings', function (Blueprint $table) {
            $table->integer('order_status')->default(1)->after('logo')->comment('1=can order, 0=cannot order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companny_settings', function (Blueprint $table) {
            $table->dropColumn('order_status');
        });
    }
};
