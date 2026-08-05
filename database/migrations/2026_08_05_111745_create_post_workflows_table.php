<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_workflows', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('post_id')->unique()->constrained('blog_posts')->cascadeOnDelete();
            $blueprint->string('file_hash')->unique();
            $blueprint->enum('status', [
                'discovered',
                'proxy_created',
                'uploaded',
                'described',
                'embedded',
                'completed',
            ])->default('discovered');
            $blueprint->timestamp('captured_at')->nullable();
            $blueprint->text('embedding')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_workflows');
    }
};
