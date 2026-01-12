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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('commission', 10, 2)->default(0.00)->after('total_price');
            $table->decimal('vat', 10, 2)->default(0.00)->after('commission');
            $table->integer('vat_percentage')->default(0)->after('vat');
            $table->integer('commission_percentage')->default(0)->after('vat_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('commission');
            $table->dropColumn('vat');
            $table->dropColumn('vat_percentage');
            $table->dropColumn('commission_percentage');
        });
    }
};
