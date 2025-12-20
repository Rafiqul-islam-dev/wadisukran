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
            $table->enum('draw_type', ['once', 'regular']);
            $table->date('draw_date')->nullable();
            $table->time('draw_time')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('type')->default('product');
            $table->integer('pick_number');
            $table->enum('prize_type',['bet', 'number']);
            $table->integer('type_number');
            $table->json('prizes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
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
