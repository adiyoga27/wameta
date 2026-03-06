<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('message_template_id')->constrained('message_templates')->onDelete('cascade');
            $table->string('name');
            $table->enum('status', ['draft', 'sending', 'completed', 'failed'])->default('draft');
            $table->integer('total')->default(0);
            $table->integer('sent')->default(0);
            $table->integer('delivered')->default(0);
            $table->integer('read')->default(0);
            $table->integer('failed')->default(0);
            $table->timestamps();
        });

        Schema::create('broadcast_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->string('wa_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_contacts');
        Schema::dropIfExists('broadcasts');
    }
};
