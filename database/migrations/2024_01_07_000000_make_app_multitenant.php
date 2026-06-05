<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-tenant: tiap data laundry dimiliki seorang member (user_id).
 * Tanpa FK constraint (SQLite-friendly) — relasi diatur lewat Eloquent + global scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'services', 'orders', 'expenses', 'settings'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('user_id')->nullable()->index()->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (['customers', 'services', 'orders', 'expenses', 'settings'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('user_id');
            });
        }
    }
};
