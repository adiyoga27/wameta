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
        Schema::table('devices', function (Blueprint $table) {
            $table->decimal('pricing_marketing', 10, 2)->default(0)->after('balance');
            $table->decimal('pricing_utility', 10, 2)->default(0)->after('pricing_marketing');
            $table->decimal('pricing_authentication', 10, 2)->default(0)->after('pricing_utility');
            $table->decimal('pricing_service', 10, 2)->default(0)->after('pricing_authentication');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_marketing',
                'pricing_utility',
                'pricing_authentication',
                'pricing_service',
            ]);
        });
    }
};
