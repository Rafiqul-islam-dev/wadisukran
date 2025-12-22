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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 10, 2);
            $table->enum('draw_type', ['once', 'daily', 'hourly']);
            $table->date('draw_date')->nullable();
            $table->time('draw_time')->nullable();
            $table->string('image')->nullable();
            $table->integer('pick_number');
            $table->enum('prize_type',['bet', 'number']);
            $table->integer('type_number');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
