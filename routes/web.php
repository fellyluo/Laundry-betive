<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberSignupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// ---- Root: tanpa landing internal (landing ada di website terpisah). Arahkan ke login. ----
Route::redirect('/', '/login');

// ---- Daftar jadi member/pengguna aplikasi (publik) ----
Route::get('/daftar-member', [MemberSignupController::class, 'show'])->name('member.signup');
Route::post('/daftar-member', [MemberSignupController::class, 'store'])->name('member.signup.store');

// ---- Pendaftaran pelanggan via QR, per member laundry (publik) ----
Route::get('/daftar/{user}', [RegistrationController::class, 'show'])->name('register.show');
Route::post('/daftar/{user}', [RegistrationController::class, 'store'])->name('register.store');

// ---- Authentication (public) ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // Backstop anti brute-force per IP (cap kasar); throttle halus per-username ada di controller.
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt')
        ->middleware('throttle:20,1');
    // Info "lupa password": tidak ada reset mandiri (rawan ambil-alih akun). Arahkan hubungi admin.
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.forgot');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ---- Application (requires login) ----
Route::middleware('auth')->group(function () {
    // Halaman blokir langganan (tanpa gating 'subscription')
    Route::get('/langganan', [SubscriptionController::class, 'blocked'])->name('langganan.blocked');

    // Toggle mode tema (semua user login: super admin -> platform settings, member -> miliknya)
    Route::post('/settings/theme-mode', [SettingController::class, 'themeMode'])->name('settings.themeMode');

    // Super Admin: kelola member & langganan
    Route::middleware('superadmin')->group(function () {
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::put('/members/profile', [MemberController::class, 'updateProfile'])->name('members.profile');
        Route::put('/members/{user}', [MemberController::class, 'update'])->name('members.update');
        Route::post('/members/{user}/toggle', [MemberController::class, 'toggle'])->name('members.toggle');
        Route::put('/members/{user}/password', [MemberController::class, 'password'])->name('members.password');
        Route::delete('/members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');

        // Pengaturan platform super admin (logo, nama, tema) + akun
        Route::get('/pengaturan', [MemberController::class, 'settings'])->name('platform.settings');
        Route::post('/pengaturan', [MemberController::class, 'saveSettings'])->name('platform.settings.save');
    });

    Route::middleware('subscription')->group(function () {
        // Dashboard: super admin -> monitoring; member -> dashboard laundrynya
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ---- Operasional laundry (khusus member) ----
        Route::middleware('member')->group(function () {
            // Orders
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/baru', [OrderController::class, 'create'])->name('orders.create');
            Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
            Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
            Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
            Route::post('/orders/{order}/payment', [OrderController::class, 'addPayment'])->name('orders.payment');
            Route::post('/orders/{order}/redeem', [OrderController::class, 'redeemPoints'])->name('orders.redeem');

            // Customers
            Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/{customer}/poin', [CustomerController::class, 'points'])->name('customers.points');
            Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
            Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

            // Services
            Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
            Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
            Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
            Route::post('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
            Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

            // Expenses
            Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

            // Settings (laundry)
            Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingController::class, 'save'])->name('settings.save');
        });
    });
});
