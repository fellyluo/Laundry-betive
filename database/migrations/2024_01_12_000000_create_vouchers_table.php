<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('diskon')->default(0)->after('diskon_poin');       // potongan manual/voucher (Rp)
            $table->string('voucher_code', 40)->nullable()->after('diskon');   // kode voucher yang dipakai (jika ada)
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kode', 40);
            $table->enum('tipe', ['nominal', 'persen'])->default('nominal');
            $table->integer('nilai')->default(0);        // rupiah (nominal) atau persen 1-100
            $table->integer('min_belanja')->default(0);  // syarat minimal subtotal
            $table->integer('kuota')->nullable();        // batas total pemakaian (null = tak terbatas)
            $table->integer('terpakai')->default(0);
            $table->boolean('aktif')->default(true);
            $table->date('kadaluarsa')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kode']); // kode unik per member
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['diskon', 'voucher_code']);
        });
    }
};
