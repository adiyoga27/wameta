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
        Schema::create('conversation_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('contact_number');
            $table->foreignId('chat_label_id')->constrained('chat_labels')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['device_id', 'contact_number', 'chat_label_id'], 'conv_label_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_labels');
    }
};
