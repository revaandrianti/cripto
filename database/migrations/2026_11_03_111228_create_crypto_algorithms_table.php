<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_algorithms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('crypto_categories')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->string('security_status')->default('educational');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_algorithms');
    }
};
