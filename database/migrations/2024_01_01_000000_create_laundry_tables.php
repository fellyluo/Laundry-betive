<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_hp', 30);
            $table->text('alamat')->nullable();
            $table->integer('poin')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('satuan', ['kg', 'pcs'])->default('kg');
            $table->integer('tarif')->default(0); // rupiah integer
            $table->enum('kategori', ['laundry', 'sabun'])->default('laundry');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_nota', 30)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamp('tanggal_masuk')->nullable();
            $table->timestamp('estimasi_selesai')->nullable();
            $table->enum('status', ['diterima', 'diproses', 'selesai', 'diambil', 'dibatalkan'])->default('diterima');
            $table->integer('total')->default(0);
            $table->enum('status_bayar', ['belum', 'dp', 'lunas'])->default('belum');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->decimal('qty', 10, 2)->default(1);
            $table->integer('harga_satuan')->default(0);
            $table->integer('subtotal')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->integer('jumlah')->default(0);
            $table->string('metode')->default('cash');
            $table->timestamps();
        });

        Schema::create('status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('status', ['diterima', 'diproses', 'selesai', 'diambil', 'dibatalkan']);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('status_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('services');
        Schema::dropIfExists('customers');
    }
};
