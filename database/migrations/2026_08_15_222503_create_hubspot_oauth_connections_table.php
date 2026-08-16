<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubspot_oauth_connections', function (Blueprint $blueprint): void {
            $blueprint->ulid('id')->primary();
            $blueprint->string('hub_id')->unique();
            $blueprint->string('tenant_id');
            $blueprint->text('access_token');
            $blueprint->text('refresh_token');
            $blueprint->timestamp('expires_at')->nullable();
            $blueprint->json('scopes')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hubspot_oauth_connections');
    }
};
