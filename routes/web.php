<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Pages for people who are NOT logged in
Route::middleware('guest')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Request a new account (the admin must approve it)
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Pages for logged-in people
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // The home address sends people to the Dashboard
    Route::get('/', function () {
        return redirect()->route('overview');
    });

    // Main pages
    Route::get('/overview', [PageController::class, 'overview'])->name('overview');
    Route::get('/availability', [PageController::class, 'availability'])->name('availability');
    Route::post('/availability/stock', [PageController::class, 'addStock'])->name('availability.stock');
    Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');
    Route::get('/notifications/export', [PageController::class, 'exportNotifications'])->name('notifications.export');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/reports/export', [PageController::class, 'export'])->name('reports.export');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::post('/settings/locations', [PageController::class, 'storeLocation'])->name('settings.locations');
    Route::put('/settings/locations/{location}', [PageController::class, 'updateLocation'])->name('settings.locations.update');
    Route::delete('/settings/locations/{location}', [PageController::class, 'destroyLocation'])->name('settings.locations.destroy');
    Route::get('/catalog', [PageController::class, 'catalog'])->name('catalog');

    // Resource routes: one line makes the add, edit, save and delete routes
    Route::resource('catalog', ProductController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['catalog' => 'product']);

    Route::resource('users', UserController::class)
        ->only(['index', 'edit', 'update', 'destroy']);
    Route::post('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::delete('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');

    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/{order}/approve', [OrderController::class, 'approve'])->name('orders.approve');
        Route::post('/{order}/reject', [OrderController::class, 'reject'])->name('orders.reject');
        Route::post('/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/{order}/receive', [OrderController::class, 'receive'])->name('orders.receive');
    });

    // My profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});