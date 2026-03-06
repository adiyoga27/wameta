<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add balance to devices
        Schema::table('devices', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->after('is_active');
        });

        // Create topups table
        Schema::create('topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('payment_type')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('status')->default('pending'); // pending, settlement, expire, cancel, deny, failure
            $table->string('snap_token')->nullable();
            $table->string('redirect_url')->nullable();
            $table->json('midtrans_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
        Schema::dropIfExists('topups');
    }
};
