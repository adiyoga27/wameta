<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type')->default('unknown'); // messages, statuses, errors, unknown
            $table->string('phone_number_id')->nullable();
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
