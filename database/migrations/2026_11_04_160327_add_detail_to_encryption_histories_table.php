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
        Schema::table('encryption_histories', function (Blueprint $table) {
            $table->string('message_preview')
                ->nullable()
                ->after('input_type');

            $table->text('error_message')
                ->nullable()
                ->after('result_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encryption_histories', function (Blueprint $table) {
            $table->dropColumn(['message_preview', 'error_message']);
        });
    }
};
