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
            $table->string('risk_status')->nullable()->index()->after('is_claimed');
            $table->text('risk_reason')->nullable()->after('risk_status');
            $table->timestamp('risk_hold_at')->nullable()->after('risk_reason');
            $table->foreignId('risk_hold_by')->nullable()->after('risk_hold_at')->constrained('users')->nullOnDelete();
            $table->timestamp('risk_release_at')->nullable()->after('risk_hold_by');
            $table->foreignId('risk_release_by')->nullable()->after('risk_release_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_tickets', function (Blueprint $table) {
            $table->dropForeign(['risk_hold_by']);
            $table->dropForeign(['risk_release_by']);
            $table->dropColumn([
                'risk_status',
                'risk_reason',
                'risk_hold_at',
                'risk_hold_by',
                'risk_release_at',
                'risk_release_by',
            ]);
        });
    }
};
