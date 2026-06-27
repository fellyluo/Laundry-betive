<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Poin loyalitas kini diberikan saat order LUNAS (bukan saat order dibuat).
 * Flag ini mencegah pemberian poin ganda untuk order yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->boolean('poin_awarded')->default(false)->after('status_bayar');
        });

        // Order lama: di logika sebelumnya poin sudah diberikan saat order dibuat.
        // Tandai semuanya sudah-diberi agar tidak dihitung ulang oleh logika baru.
        DB::table('orders')->update(['poin_awarded' => true]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn('poin_awarded');
        });
    }
};
