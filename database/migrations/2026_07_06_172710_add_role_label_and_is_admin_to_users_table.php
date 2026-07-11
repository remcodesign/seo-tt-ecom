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
        Schema::table('users', function (Blueprint $blueprint): void {
            $blueprint->after('email', function (Blueprint $blueprint): void {
                $blueprint->enum('role_label', ['guest', 'user', 'writer', 'admin'])->default('guest');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $blueprint): void {
            $blueprint->dropColumn('role_label');
        });
    }
};
