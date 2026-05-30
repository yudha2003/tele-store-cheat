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
        Schema::create('denoms', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_id')->default(0);
            $table->integer('game_id')->default(0);
            $table->string('name')->nullable();
            $table->integer('price')->default(0);
            $table->integer('duration')->default(0);
            $table->enum('status', ['active', 'inactive']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('denoms');
    }
};
