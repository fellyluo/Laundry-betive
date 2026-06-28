<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom potongan poin pada order (redeem poin jadi diskon).
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('poin_redeemed')->default(0)->after('poin_awarded'); // jumlah poin yang ditukar
            $table->integer('diskon_poin')->default(0)->after('poin_redeemed');   // nilai potongan (Rp) dari poin
        });

        // Buku besar (ledger) poin: setiap penambahan/pengurangan poin tercatat di sini.
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();          // tenant (member laundry)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('type', ['earn', 'redeem', 'reversal', 'adjust'])->default('earn');
            $table->integer('points'); // bertanda: (+) menambah saldo, (-) mengurangi saldo poin pelanggan
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['poin_redeemed', 'diskon_poin']);
        });
    }
};
