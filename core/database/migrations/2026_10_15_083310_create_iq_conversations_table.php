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
        Schema::create('iq_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->text('query');
            $table->text('answer');
            $table->json('tools_called')->nullable();
            $table->string('user_fingerprint', 16)->nullable();
            $table->timestamps();

            $table->index(['product', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iq_conversations');
    }
};
