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
        Schema::create('posts', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
            $blueprint->string('title');
            $blueprint->text('body')->nullable();
            $blueprint->string('slug')->unique();
            $blueprint->dateTime('published_on')->nullable();
            $blueprint->timestamps();

            $blueprint->index(['user_id', 'published_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
