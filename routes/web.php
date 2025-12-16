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





require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified', 'isActive'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('can:show dashboard')->name('dashboard');
    // Role Management Routes
    Route::prefix('roles')->name('roles.')->middleware('can:show roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/store', [RoleController::class, 'store'])->middleware('can:role create')->name('store');
        Route::post('/update/{role}', [RoleController::class, 'update'])->middleware('can:role update')->name('update');
        Route::delete('/delete/{role}', [RoleController::class, 'delete'])->middleware('can:role update')->name('delete');
    });

    // Permissions
    Route::prefix('permissions')->name('permissions.')->middleware('can:show permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::post('/store', [PermissionController::class, 'store'])->middleware('can:permission create')->name('store');
        Route::post('/update/{permission}', [PermissionController::class, 'update'])->middleware('can:permission update')->name('update');
        Route::delete('/delete/{permission}', [PermissionController::class, 'delete'])->middleware('can:permission delete')->name('delete');
    });

    // User Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('can:show users')->name('index');
        Route::post('/store', [UserController::class, 'store'])->middleware('can:user create')->name('store');
        Route::post('/update/{user}', [UserController::class, 'update'])->middleware('can:user update')->name('update');
        Route::get('/status-change/{user}', [UserController::class, 'updateStatus'])->middleware('can:user status change')->name('status-change');
        Route::delete('/delete/{user}', [UserController::class, 'delete'])->middleware('can:user delete')->name('delete');
        Route::get('/trashed', [UserController::class, 'trashed_users'])->middleware('can:show trashed users')->name('trashed');
        Route::get('/restore/{user}', [UserController::class, 'restore_user'])->middleware('can:user restore')->name('restore');
        Route::delete('/permanent-delete/{user}', [UserController::class, 'permanent_delete_user'])->middleware('can:user permanent delete')->name('permanent-delete');
    });
    // Agent Management Routes
    Route::prefix('agents')->name('agents.')->middleware('can:show agent list')->group(function () {
        Route::get('/', [AgentController::class, 'index'])->name('index');
        Route::post('/store', [AgentController::class, 'store'])->middleware('can:agent create')->name('store');
        Route::post('/update/{user}', [AgentController::class, 'update'])->middleware('can:agent update')->name('update');
        Route::delete('/delete/{user}', [AgentController::class, 'delete'])->middleware('can:agent delete')->name('delete');
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
