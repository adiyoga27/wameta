<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('from_number');
            $table->string('from_name')->nullable();
            $table->string('message_type')->default('text');
            $table->text('message_body')->nullable();
            $table->text('media_url')->nullable();
            $table->string('wa_message_id')->nullable();
            $table->timestamp('wa_timestamp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_messages');
    }
};
