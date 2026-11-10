<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_point_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 40)->unique();
            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected

            // Business information
            $table->string('shop_name');
            $table->string('owner_name');
            $table->text('passport_no');   // encrypted cast
            $table->text('uid_no');        // encrypted cast
            $table->text('eid_no');        // encrypted cast
            $table->string('trade_license_no', 100)->index();
            $table->string('email')->index();
            $table->string('mobile_country_code', 8)->default('+971');
            $table->string('mobile', 30)->index();
            $table->string('whatsapp_country_code', 8)->default('+971');
            $table->string('whatsapp', 30);
            $table->text('address');
            $table->string('location_map', 2048);
            $table->string('photo_path')->nullable();

            // Products
            $table->json('products')->nullable();
            $table->string('products_other_text')->nullable();

            // Device & service allocation
            $table->boolean('dev_tab')->default(false);
            $table->boolean('dev_pos_machine')->default(false);
            $table->boolean('dev_sim_card')->default(false);
            $table->boolean('dev_active_data')->default(false);
            $table->boolean('sim_card_provided')->default(false);
            $table->date('delivery_date');
            $table->string('delivery_time', 5); // HH:MM

            // Signatures
            $table->string('person_signature_path');
            $table->string('company_signature_path');
            $table->date('signed_date');
            $table->string('signed_place');

            // Workflow
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->unsignedSmallInteger('terms_version')->default(1);
            $table->json('terms_snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });

        Schema::create('sales_point_registration_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('sales_point_registrations')->cascadeOnDelete();
            $table->string('type', 30); // trade_license|passport|visa|emirates_id|business_card|shop_picture
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();

            $table->unique(['registration_id', 'type'], 'sprd_registration_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_point_registration_documents');
        Schema::dropIfExists('sales_point_registrations');
    }
};
