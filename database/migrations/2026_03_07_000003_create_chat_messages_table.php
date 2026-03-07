<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('contact_number');
            $table->string('contact_name')->nullable();
            $table->enum('direction', ['in', 'out']);
            $table->string('message_type')->default('text');
            $table->text('message_body')->nullable();
            $table->text('media_url')->nullable();
            $table->string('wa_message_id')->nullable();
            $table->timestamp('wa_timestamp')->nullable();
            $table->string('status')->default('sent'); // sent, delivered, read, failed
            $table->timestamps();

            $table->index(['device_id', 'contact_number']);
            $table->index('wa_message_id');
            $table->index('wa_timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
