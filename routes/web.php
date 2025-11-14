<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Agent\AgentController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Banner\AppBannerController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Order\OrderController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');



Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';


Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
Route::put('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');


Route::middleware(['auth', 'verified'])->group(function () {

    // Role Management Routes
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // User Management Routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::resource('banners', AppBannerController::class);
    Route::resource('company-settings', SettingsController::class)->except(['show', 'create', 'edit']);
    Route::resource('products', ProductController::class);
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    // Additional banner routes
    Route::prefix('banners')->name('banners.')->group(function () {

        // Toggle banner status
        Route::patch('{banner}/toggle-status', [AppBannerController::class, 'toggleStatus'])
            ->name('toggle-status');

        // Bulk operations
        Route::post('bulk-delete', [AppBannerController::class, 'bulkDelete'])
            ->name('bulk-delete');

        Route::post('bulk-update-status', [AppBannerController::class, 'bulkUpdateStatus'])
            ->name('bulk-update-status');

        // Search banners
        Route::get('search', [AppBannerController::class, 'search'])
            ->name('search');
    });




});
