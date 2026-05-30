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
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->json('temporary_data')->nullable();
            $table->string('invoice_id')->nullable();
            $table->json('product')->nullable();
            $table->json('payment')->nullable();
            $table->integer('price')->default(0);
            $table->text('notes')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('process_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->dateTime('expire_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
