<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_recommendation_tasks', function (Blueprint $blueprint): void {
            $blueprint->json('debug_trace')->nullable()->after('result');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_recommendation_tasks', function (Blueprint $blueprint): void {
            $blueprint->dropColumn('debug_trace');
        });
    }
};
