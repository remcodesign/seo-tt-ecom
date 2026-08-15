<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_recommendation_tasks', function (Blueprint $blueprint): void {
            $blueprint->string('note_deal_id')->nullable()->after('deal_id');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_recommendation_tasks', function (Blueprint $blueprint): void {
            $blueprint->dropColumn('note_deal_id');
        });
    }
};
