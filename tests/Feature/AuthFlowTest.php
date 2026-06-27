<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    // ---- #11 Password minimal 8 karakter ----
    public function test_signup_menolak_password_pendek(): void
    {
        $this->post(route('member.signup.store'), [
            'name' => 'Laundry Baru',
            'phone' => '081234567890',
            'username' => 'laundrybaru',
            'password' => '123456', // 6 karakter -> ditolak
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['username' => 'laundrybaru']);
    }

    public function test_signup_menerima_password_8_karakter(): void
    {
        $this->post(route('member.signup.store'), [
            'name' => 'Laundry Baru',
            'phone' => '081234567890',
            'username' => 'laundrybaru',
            'password' => 'rahasia8',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['username' => 'laundrybaru', 'is_active' => false]);
    }

    // ---- #2 Lockout login setelah percobaan gagal beruntun ----
    public function test_login_dikunci_setelah_percobaan_gagal_beruntun(): void
    {
        User::create([
            'name' => 'Owner', 'username' => 'owner',
            'password' => Hash::make('benarsekali'), 'role' => 'member', 'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), ['username' => 'owner', 'password' => 'salah']);
        }

        // Percobaan ke-6 (bahkan dengan password benar) harus tertahan oleh lockout.
        $this->post(route('login.attempt'), ['username' => 'owner', 'password' => 'benarsekali'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
