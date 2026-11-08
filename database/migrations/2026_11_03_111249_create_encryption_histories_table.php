<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encryption_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('algorithm_id')
                ->nullable()
                ->constrained('crypto_algorithms')
                ->nullOnDelete();

            $table->string('operation');
            $table->string('input_type')->default('text');
            $table->string('result_status')->default('success');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encryption_histories');
    }
};
