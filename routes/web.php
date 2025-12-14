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
use App\Http\Controllers\Role\PermissionController;

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
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/store', [RoleController::class, 'store'])->name('store');
        Route::post('/update/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/delete/{role}', [RoleController::class, 'delete'])->name('delete');
    });

    // Permissions
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::post('/store', [PermissionController::class, 'store'])->name('store');
        Route::post('/update/{permission}', [PermissionController::class, 'update'])->name('update');
        Route::delete('/delete/{permission}', [PermissionController::class, 'delete'])->name('delete');
    });

    // User Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/store', [UserController::class, 'store'])->name('store');
        Route::post('/update/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
        Route::delete('/update/{user}', [UserController::class, 'delete'])->name('delete');
    });

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
