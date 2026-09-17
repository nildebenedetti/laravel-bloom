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
        Schema::create('record_emotion', function (Blueprint $table) {
            $table->id();

            //defining foreign keys for pivot table
            $table->foreignId('record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emotion_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_emotion');
    }
};
