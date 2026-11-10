<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_point_registration_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('sales_point_registrations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30); // created|updated|approved|rejected|pdf_regenerated|pdf_viewed|pdf_downloaded|document_viewed|deleted
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['registration_id', 'created_at'], 'sprev_registration_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_point_registration_events');
    }
};
