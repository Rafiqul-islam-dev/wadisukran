<?php

use App\Http\Controllers\Accounts\AccountsLedgerController;
use App\Http\Controllers\Accounts\AccountsSummeryController;
use App\Http\Controllers\Payment\PaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Agent\AgentController;
use App\Http\Controllers\Agent\AgentHistoryController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Banner\AppBannerController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\ProductWiseSalesController;
use App\Http\Controllers\Product\CheckWinController;
use App\Http\Controllers\Product\DrawController;
use App\Http\Controllers\Report\WinnerReportController;
use App\Http\Controllers\Role\PermissionController;
use App\Http\Controllers\Report\CancelReportController;
use App\Http\Controllers\User\CustomerController;
use App\Http\Controllers\Report\WinnerReportAgentController;
use App\Http\Controllers\Report\CancelRequestController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified', 'isActive'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('can:show dashboard')->name('dashboard');
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
        Route::get('/trashed', [AgentController::class, 'trashed_agents'])->middleware('can:show trashed agents')->name('trashed');
        Route::get('/restore/{user}', [AgentController::class, 'restore_agent'])->middleware('can:agent restore')->name('restore');
        Route::delete('/permanent-delete/{user}', [AgentController::class, 'permanent_delete_agent'])->middleware('can:agent permanent delete')->name('permanent-delete');
        Route::get('/top-ten', [AgentController::class, 'top_ten_agents'])->name('top-ten');

        Route::get('/history', [AgentHistoryController::class, 'index'])->name('history');
        Route::get('/print-pdf', [AgentController::class, 'printPdf'])->name('printPdf');
    });

    Route::prefix('accounts')->name('accounts.')->middleware('can:show agent list')->group(function () {
        Route::get('/summery', [AccountsSummeryController::class, 'index'])->name('summery');
        Route::get('/ledger', [AccountsLedgerController::class, 'index'])->name('ledger');
        Route::post('/ledger-store', [AccountsLedgerController::class, 'store'])->name('ledger-store');
        Route::post('/generate-bill', [AccountsSummeryController::class, 'generateBill'])->name('generate-bill');
        Route::get('/bills', [AccountsSummeryController::class, 'bills'])->name('bill');
    });


    Route::middleware('can:show categories')->resource('categories', CategoryController::class);
    Route::get('categories/status-change/{category}', [CategoryController::class, 'status_change'])->name('categories.status-change');
    Route::resource('slides', AppBannerController::class);
    Route::resource('company-settings', SettingsController::class)->except(['show', 'create', 'edit']);

    // Product Routes
    Route::resource('products', ProductController::class)
        ->except(['update']);
    Route::post('products/update/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::put('products/status-change/{product}', [ProductController::class, 'status_change'])->name('products.status-change');

    Route::get('trashed-products', [ProductController::class, 'trashed_products'])->name('products.trashed');
    Route::get('products-permanent-delete/{product}', [ProductController::class, 'permanent_delete'])->name('products.permanent-delete');
    Route::get('products-restore/{product}', [ProductController::class, 'restore_product'])->name('products.restore');

    Route::prefix('draws')->name('draws.')->middleware('can:show draws')->group(function () {
        Route::get('/', [DrawController::class, 'index'])->name('index');
        Route::post('store', [DrawController::class, 'store'])->name('store');
        Route::get('histories', [DrawController::class, 'histories'])->name('histories');
        Route::get('histories/delete/{win}', [DrawController::class, 'histories_delete'])->name('histories-delete');
    });
    Route::get('/check-wins', [CheckWinController::class, 'index'])->name('check-wins');
    Route::post('/check-win', [CheckWinController::class, 'check_win'])->name('check-win');
    Route::post('/claim-win', [CheckWinController::class, 'claim_win'])->name('claim-win');

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/update-status/{order}', [OrderController::class, 'updateStatus'])->name('update-status');
    });
    Route::prefix('sales-report')->name('sales-report.')->group(function () {
        Route::get('/product-wise', [ProductWiseSalesController::class, 'index'])->name('index');
    });

    Route::prefix('probable-wins')->name('probable-wins.')->middleware('can:show probable wins')->group(function () {
        Route::get('/', [OrderController::class, 'probableWins'])->name('index');
    });

    Route::prefix('customers')->name('customers.')->middleware('can:show customers')->group(function () {
        Route::get('/', [CustomerController::class, 'customer_list'])->name('index');
        Route::get('top-ten', [CustomerController::class, 'top_ten_customers'])->name('top-ten');
    });


    // Additional banner routes
    Route::prefix('slides')->name('slides.')->group(function () {
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

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('winner-report', [WinnerReportController::class, 'winnerReport'])->name('winner-report');
        Route::get('winner-report-agent', [WinnerReportAgentController::class, 'winnerReportAgent'])->name('winner-report-agent');
        Route::get('cancel-report', [CancelReportController::class, 'cancelReport'])->name('cancel-report');
        Route::get('winner-report-agent/details', [WinnerReportAgentController::class, 'winnerReportAgentDetails'])->name('winner-report-agent.details');
        Route::get('cancel-request', [CancelRequestController::class, 'cancelRequest'])->name('cancel-request');
        Route::put('cancel-accept/{order}', [CancelRequestController::class, 'acceptCancel'])->name('cancel-accept');
    });


    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('all-payment', [PaymentController::class, 'allPayment'])->name('all-payment');
    });

    Route::get('login-as-agent', [AgentController::class, 'loginAs'])->middleware('can:login as agent')->name('login-as-agent');

});
