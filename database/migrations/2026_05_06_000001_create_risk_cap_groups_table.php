<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_cap_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('draw_number')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('capped');
            $table->decimal('cap_percent', 8, 2)->default(40);
            $table->decimal('total_sale', 12, 2)->default(0);
            $table->decimal('commission_percent', 8, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('net_sale', 12, 2)->default(0);
            $table->decimal('max_payable_amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'draw_number', 'user_id'], 'risk_cap_group_unique');
            $table->index(['status', 'product_id', 'draw_number', 'user_id'], 'risk_cap_group_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_cap_groups');
    }
};
