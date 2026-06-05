<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('metode_bayar')->nullable()->after('alamat'); // metode bayar favorit
            $table->boolean('via_qr')->default(false)->after('metode_bayar'); // daftar mandiri via QR
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['metode_bayar', 'via_qr']);
        });
    }
};
