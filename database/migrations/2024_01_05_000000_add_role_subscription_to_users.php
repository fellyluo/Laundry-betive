<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('username');     // super_admin | member
            $table->boolean('is_active')->default(true)->after('role');        // aktif / suspend
            $table->date('subscribed_until')->nullable()->after('is_active');  // masa berlaku sewa (null = tanpa batas)
            $table->string('plan')->nullable()->after('subscribed_until');     // label paket
            $table->integer('plan_price')->default(0)->after('plan');          // harga sewa per member (catatan)
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'subscribed_until', 'plan', 'plan_price']);
        });
    }
};
