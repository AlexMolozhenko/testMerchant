<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('merchant_apps', function (Blueprint $table): void {
            $table->string('name', 100)->after('client_secret');
            $table->string('mode', 20)->default('test')->after('name');
            $table->json('permissions')->nullable()->after('mode');
            $table->unsignedInteger('rate_limit_per_minute')->nullable()->after('permissions');
            $table->string('status', 20)->default('active')->after('rate_limit_per_minute');
            $table->timestamp('last_used_at')->nullable()->after('status');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('merchant_apps', function (Blueprint $table): void {
            $table->dropColumn(['name', 'mode', 'permissions', 'rate_limit_per_minute', 'status', 'last_used_at']);
        });
    }
};
