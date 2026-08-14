<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_recommendation_tasks', function (Blueprint $blueprint): void {
            $blueprint->string('note_id')->nullable()->after('callback_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_recommendation_tasks', function (Blueprint $blueprint): void {
            $blueprint->dropColumn('note_id');
        });
    }
};
