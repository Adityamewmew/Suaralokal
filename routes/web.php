<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SidebarMenuController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BangjekOrderController;
use App\Http\Controllers\Driver\DriverOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\App\ConversationController;
use App\Http\Controllers\App\DiscoveryController;
use App\Http\Controllers\App\OrderController;
use App\Http\Controllers\App\UmkmProfileController;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('test', function () {
    Benchmark::dd(function () {
        (string) view('welcome');
    });
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'doLogin'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Users Routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::middleware('access_type:1')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/add', [UserController::class, 'add'])->name('add');
        Route::post('/create', [UserController::class, 'doCreate'])->name('create');
        Route::get('/detail/{id}', [UserController::class, 'detail'])->name('detail');
        Route::get('/update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('/update/{id}', [UserController::class, 'doUpdate'])->name('doUpdate');
        Route::delete('/delete/{id}', [UserController::class, 'delete'])->name('delete');
        Route::post('/reset-password/{id}', [UserController::class, 'resetPassword'])->name('resetPassword');
    });

    Route::middleware('access_type:1')->prefix('sidebar-menu')->name('sidebar_menu.')->group(function () {
        Route::get('/', [SidebarMenuController::class, 'index'])->name('index');
        Route::get('/refresh-cache', [SidebarMenuController::class, 'refreshCache'])->name('refresh_cache');
        Route::get('/add', [SidebarMenuController::class, 'add'])->name('add');
        Route::post('/create', [SidebarMenuController::class, 'doCreate'])->name('create');
        Route::get('/update/{id}', [SidebarMenuController::class, 'update'])->name('update');
        Route::post('/update/{id}', [SidebarMenuController::class, 'doUpdate'])->name('doUpdate');
        Route::delete('/delete/{id}', [SidebarMenuController::class, 'delete'])->name('delete');
        Route::get('/{id}/access', [SidebarMenuController::class, 'access'])->name('access');
        Route::post('/{id}/access', [SidebarMenuController::class, 'doAccess'])->name('doAccess');
        Route::get('/role-access/{accessType}', [SidebarMenuController::class, 'roleAccess'])->name('role_access');
        Route::post('/role-access/{accessType}', [SidebarMenuController::class, 'doRoleAccess'])->name('doRoleAccess');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/change-password', [UserController::class, 'changePassword'])->name('change_password');
        Route::post('/change-password', [UserController::class, 'doChangePassword'])->name('do_change_password');
    });

});

// SuaraLokal MVP App Routes
Route::middleware(['auth', 'role:umkm'])->prefix('app/umkm/profile')->name('app.umkm.profile.')->group(function () {
    Route::get('/', [UmkmProfileController::class, 'edit'])->name('edit');
    Route::post('/save', [UmkmProfileController::class, 'doSave'])->name('save');
});

// Discovery — pengguna only
Route::middleware(['auth', 'role:pengguna'])->prefix('app')->name('app.')->group(function () {
    Route::get('/discovery', [DiscoveryController::class, 'index'])->name('discovery');
});

// Conversations — pengguna and UMKM share the same thread
Route::middleware(['auth', 'role:pengguna,umkm'])->prefix('app/conversations')->name('app.conversations.')->group(function () {
    Route::get('/{peerId}', [ConversationController::class, 'show'])->name('show');
    Route::get('/{conversation}/messages', [ConversationController::class, 'messages'])->name('messages.index');
    Route::post('/{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('messages.store');
});

// Order creation — UMKM only
Route::middleware(['auth', 'role:umkm'])->prefix('app/orders')->name('app.orders.')->group(function () {
    Route::post('/{conversation}/create', [OrderController::class, 'store'])->name('store');
});

// Order confirmation — pengguna only
Route::middleware(['auth', 'role:pengguna'])->prefix('app/orders')->name('app.orders.')->group(function () {
    Route::post('/{order}/confirm', [OrderController::class, 'confirm'])->name('confirm');
});

// Admin Bangjek Assignment — ojek_admin & superadmin only
Route::middleware(['auth', 'role:ojek_admin,superadmin'])->prefix('admin/bangjek-orders')->name('admin.bangjek_orders.')->group(function () {
    Route::get('/', [BangjekOrderController::class, 'index'])->name('index');
    Route::get('/detail/{id}', [BangjekOrderController::class, 'detail'])->name('detail');
    Route::post('/assign/{id}', [BangjekOrderController::class, 'assign'])->name('assign');
});

// Driver Order Workflow — driver only
Route::middleware(['auth', 'role:driver'])->prefix('driver/orders')->name('driver.orders.')->group(function () {
    Route::get('/', [DriverOrderController::class, 'index'])->name('index');
    Route::get('/{id}', [DriverOrderController::class, 'detail'])->name('detail');
    Route::post('/{id}/pickup', [DriverOrderController::class, 'pickUp'])->name('pickup');
    Route::post('/{id}/complete', [DriverOrderController::class, 'complete'])->name('complete');
});

