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
        Schema::table('broadcast_contacts', function (Blueprint $table) {
            $table->decimal('billed_amount', 10, 2)->nullable()->after('is_billed');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->decimal('billed_amount', 10, 2)->nullable()->after('is_billed');
        });

        Schema::table('messages_tables', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_contacts', function (Blueprint $table) {
            $table->dropColumn('billed_amount');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('billed_amount');
        });

        Schema::table('messages_tables', function (Blueprint $table) {
            //
        });
    }
};
