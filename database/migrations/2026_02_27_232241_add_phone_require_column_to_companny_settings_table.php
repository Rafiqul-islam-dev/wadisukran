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
            $table->boolean('customer_phone_require')->default(false)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companny_settings', function (Blueprint $table) {
            $table->dropColumn('phone_require');
        });
    }
};
