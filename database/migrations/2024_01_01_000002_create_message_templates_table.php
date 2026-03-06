<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('language')->default('id');
            $table->enum('category', ['MARKETING', 'UTILITY', 'AUTHENTICATION'])->default('MARKETING');
            $table->enum('header_type', ['NONE', 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'])->default('NONE');
            $table->text('header_content')->nullable();
            $table->text('body');
            $table->string('footer')->nullable();
            $table->json('buttons')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'PAUSED', 'DISABLED'])->default('PENDING');
            $table->text('rejected_reason')->nullable();
            $table->string('meta_template_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
