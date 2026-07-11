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
        Schema::create('chat', function(Blueprint $table){
            $table->id();
            $table->foreign('user_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->string('image_path')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_prompt')->nullable();
             $table->longText('ai_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat');
    }
};
