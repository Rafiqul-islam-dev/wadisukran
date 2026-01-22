<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('win_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->string('invoice_no')->unique();
            $table->foreign('invoice_no')->references('invoice_no')->on('orders')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('amount', 10, 2);
            $table->foreignId('claimed_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
