<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('saldo')->default(0)->after('poin'); // dompet prabayar (Rp)
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('type', ['topup', 'payment', 'refund', 'adjust'])->default('topup');
            $table->integer('amount'); // bertanda: (+) menambah saldo, (-) mengurangi saldo
            $table->string('metode')->nullable(); // metode top-up (cash/transfer/qris)
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('saldo');
        });
    }
};
