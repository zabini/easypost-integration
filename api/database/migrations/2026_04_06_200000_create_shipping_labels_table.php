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
        Schema::create('shipping_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('easypost_shipment_id')->index();
            $table->string('easypost_rate_id')->nullable();
            $table->string('tracking_code')->nullable();
            $table->text('label_url');
            $table->string('carrier');
            $table->string('service');
            $table->string('rate_amount', 32);
            $table->string('rate_currency', 3);
            $table->string('status');
            $table->json('from_address_json');
            $table->json('to_address_json');
            $table->json('parcel_json');
            $table->json('raw_response_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_labels');
    }
};
