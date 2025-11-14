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
            $table->decimal('price', 10, 2);
            $table->date('draw_date');
            $table->time('draw_time');
            $table->string('image')->nullable();
            $table->string('type')->default('product');
            $table->integer('pick_number');
            $table->string('showing_type'); // 'prizes' or 'number'
            $table->integer('type_number');
            $table->json('prizes'); // Store prizes as JSON
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
