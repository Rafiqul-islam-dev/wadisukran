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
        Schema::create('agent_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['sell', 'posting', 'commission', 'win', 'claim']);
            $table->decimal('amount', 10, 2);
            $table->decimal('old_balance', 10, 2)->nullable();
            $table->decimal('current_balance', 10, 2)->nullable();
            $table->enum('is_checked', [0,1])->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_accounts');
    }
};
