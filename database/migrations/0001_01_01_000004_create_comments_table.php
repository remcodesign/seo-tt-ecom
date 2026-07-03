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
        Schema::create('comments', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('post_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
            $blueprint->text('comment');
            $blueprint->timestamps();

            $blueprint->index(['post_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
