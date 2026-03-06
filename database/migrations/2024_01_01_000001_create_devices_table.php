<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('waba_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('app_id')->nullable();
            $table->string('app_secret')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('device_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['device_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_user');
        Schema::dropIfExists('devices');
    }
};
