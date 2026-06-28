<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('public_token', 32)->nullable()->unique()->after('nomor_nota');
        });

        // Backfill token untuk order yang sudah ada (agar bisa dilacak juga).
        foreach (DB::table('orders')->whereNull('public_token')->pluck('id') as $id) {
            DB::table('orders')->where('id', $id)->update(['public_token' => Str::lower(Str::random(20))]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
