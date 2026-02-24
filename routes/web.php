<?php

use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ModelTypeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StoreTypeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\UtencilReportController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['guest']], function() {
    Route::get('/login', [LoginController::class , 'show'])->name('login.show');
    Route::post('/login', [LoginController::class , 'login'])->name('login.perform');
    Route::get('forget-password', [ForgotPasswordController::class , 'showLinkRequestForm'])->name('password.request');
    Route::post('forget-password', [ForgotPasswordController::class , 'sendResetLinkEmail'])->name('password.email'); 
    Route::get('reset-password/{token}', [ForgotPasswordController::class , 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ForgotPasswordController::class , 'reset'])->name('password.update');
});

Route::get('logout', [DashboardController::class, 'logout'])->name('logout');

Route::group(['middleware' => ['auth', 'permission']], function() {

    Route::any('/', [DashboardController::class, 'index'])->name('dashboard')->withoutMiddleware(['permission']);
    Route::resource('vehicles', \App\Http\Controllers\VehicleController::class);
    Route::resource('pricing-tiers', \App\Http\Controllers\PricingTierController::class);

    Route::resource('roles', RolesController::class);
    Route::resource('stores', StoreController::class);
    Route::resource('store-types', StoreTypeController::class);
    Route::resource('model-types', ModelTypeController::class);

    Route::resource('notification-templates', NotificationTemplateController::class);

    Route::group(['prefix' => 'users'], function() {
        Route::get('/', [UsersController::class, 'index'])->name('users.index');
        Route::get('/create', [UsersController::class, 'create'])->name('users.create');
        Route::post('/create', [UsersController::class, 'store'])->name('users.store');
        Route::get('/{user}/show', [UsersController::class, 'show'])->name('users.show');
        Route::get('/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::patch('/{user}/update', [UsersController::class, 'update'])->name('users.update');
        Route::delete('/{user}/delete', [UsersController::class, 'destroy'])->name('users.destroy');
        Route::delete('/{user}/restore', [UsersController::class, 'restore'])->name('users.restore');
        Route::post('users-import', [UsersController::class, 'import'])->name('users.import');
        Route::get('users-export', [UsersController::class, 'export'])->name('users.export');
        Route::delete('/{user}/remove', [UsersController::class, 'remove'])->name('users.remove');
        Route::get('/{user}/showDeleted', [UsersController::class, 'showDeleted'])->name('users.show.deleted');
        Route::get('/getUsers', [UsersController::class, 'getUsers'])->name('datatable.users')->withoutMiddleware(['permission']);
        Route::get('/getArchiveUsers', [UsersController::class, 'getArchiveUsers'])->name('datatable.users.archive')->withoutMiddleware(['permission']);
    });

    Route::resource('order-categories', \App\Http\Controllers\OrderCategoryController::class);
    Route::resource('order-units', \App\Http\Controllers\OrderUnitController::class);
    Route::get('orders/dashboard', [\App\Http\Controllers\ProductionDashboardController::class, 'dashboard'])->name('orders.dashboard');
    Route::get('orders/dashboard/export', [\App\Http\Controllers\ProductionDashboardController::class, 'export'])->name('orders.dashboard.export');
    Route::post('orders/update-status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('orders/ajax/delivery-persons', [\App\Http\Controllers\OrderController::class, 'getDeliveryPersons'])->name('orders.ajax.delivery-persons');
    Route::get('orders/delivery-challan/{id}', [\App\Http\Controllers\OrderController::class, 'getDeliveryChallan'])->name('orders.ajax.delivery-challan');
    Route::get('orders/delivery-challan-pdf/{id}', [\App\Http\Controllers\OrderController::class, 'downloadDeliveryChallan'])->name('orders.download-challan');
    Route::get('orders/invoice/{id}', [\App\Http\Controllers\OrderController::class, 'getInvoice'])->name('orders.invoice');
    Route::get('orders/invoice-pdf/{id}', [\App\Http\Controllers\OrderController::class, 'downloadInvoice'])->name('orders.download-invoice');
    Route::resource('orders', \App\Http\Controllers\OrderController::class);
    Route::match(['GET', 'PUT'], 'reorder/{id}', [\App\Http\Controllers\OrderController::class, 'reorder'])->name('orders.reorder');

    Route::post('order-products/upload-image', [\App\Http\Controllers\OrderProductController::class, 'uploadImage'])->name('order-products.upload-image');
    Route::post('order-products/delete-image', [\App\Http\Controllers\OrderProductController::class, 'deleteImage'])->name('order-products.delete-image');
    Route::resource('order-products', \App\Http\Controllers\OrderProductController::class);
    Route::post('currencies/set-default/{id}', [\App\Http\Controllers\CurrencyController::class, 'setDefault'])->name('currencies.set-default');
    Route::resource('currencies', \App\Http\Controllers\CurrencyController::class);

    Route::get('orders/ajax/products-by-category/{categoryId}', [\App\Http\Controllers\OrderController::class, 'getProductsByCategory'])->name('orders.ajax.products');
    Route::get('orders/ajax/units-by-product/{productId}', [\App\Http\Controllers\OrderController::class, 'getUnitsByProduct'])->name('orders.ajax.units');
    Route::post('orders/ajax/get-price', [\App\Http\Controllers\OrderController::class, 'getPriceByUnit'])->name('orders.ajax.price');
    Route::post('orders/ajax/get-item-details', [\App\Http\Controllers\OrderController::class, 'getItemDetails'])->name('orders.ajax.item-details');
    Route::get('orders/ajax/store-details/{id}', [\App\Http\Controllers\OrderController::class, 'getStoreDetails'])->name('orders.ajax.store-details');
    
    Route::post('orders/payment-logs/store', [\App\Http\Controllers\OrderPaymentLogController::class, 'store'])->name('orders.payment-logs.store');
    Route::delete('orders/payment-logs/{id}', [\App\Http\Controllers\OrderPaymentLogController::class, 'destroy'])->name('orders.payment-logs.destroy');

    Route::post('grievance-reporting/update-status', [\App\Http\Controllers\GrievanceController::class, 'updateStatus'])->name('grievance-reporting.update-status');
    Route::get('grievance-reporting/ajax/order-items/{orderId}', [\App\Http\Controllers\GrievanceController::class, 'getOrderItems'])->name('grievance-reporting.ajax.order-items');
    Route::resource('grievance-reporting', \App\Http\Controllers\GrievanceController::class);

    // Utencil movement report
    Route::get('utencil-report', [UtencilReportController::class, 'index'])->name('utencil-report.index');
    Route::get('utencil-report/export', [UtencilReportController::class, 'export'])->name('utencil-report.export');

    Route::get('order-products/{id}/price-management', [\App\Http\Controllers\OrderProductPriceManagementController::class, 'index'])->name('order-products-price.index');
    Route::post('order-products/{id}/price-management', [\App\Http\Controllers\OrderProductPriceManagementController::class, 'store'])->name('order-products-price.store');
    Route::post('order-products/{id}/price-management/bulk-store', [\App\Http\Controllers\OrderProductPriceManagementController::class, 'bulkStore'])->name('order-products-price.bulk-store');
    Route::get('order-products/price-management/export', [\App\Http\Controllers\OrderProductPriceManagementController::class, 'export'])->name('order-products-price.export');
    Route::post('order-products/price-management/import', [\App\Http\Controllers\OrderProductPriceManagementController::class, 'import'])->name('order-products-price.import');
    Route::get('order-products/{id}/price-management/history', [\App\Http\Controllers\OrderProductPriceManagementController::class, 'history'])->name('order-products-price.history');
    Route::delete('order-products/{id}/price-management/{price_id}', [\App\Http\Controllers\OrderProductPriceManagementController::class, 'destroy'])->name('order-products-price.destroy');

    Route::get('bulk-price-management', [\App\Http\Controllers\BulkPriceManagementController::class, 'index'])->name('bulk-price-management.index');
    Route::post('bulk-price-management', [\App\Http\Controllers\BulkPriceManagementController::class, 'store'])->name('bulk-price-management.store');
    Route::get('bulk-price-management/export', [\App\Http\Controllers\BulkPriceManagementController::class, 'export'])->name('bulk-price-management.export')->withoutMiddleware(['permission']);
    Route::get('bulk-price-management/import', [\App\Http\Controllers\BulkPriceManagementController::class, 'import'])->name('bulk-price-management.import')->withoutMiddleware(['permission']);

    Route::resource('handling-instructions', \App\Http\Controllers\HandlingInstructionController::class);
    Route::resource('tax-slabs', \App\Http\Controllers\TaxSlabController::class);
    Route::resource('other-items', \App\Http\Controllers\OtherItemController::class);

    Route::get('discount-management', [\App\Http\Controllers\DiscountManagementController::class, 'index'])->name('discount-management.index');
    Route::post('discount-management', [\App\Http\Controllers\DiscountManagementController::class, 'store'])->name('discount-management.store');

    Route::post('/get-address', [\App\Http\Controllers\OrderController::class, 'getAddress'])->name('get-address');

    Route::withoutMiddleware('permission')->group(function() {
        Route::post('state-list', [StoreController::class, 'stateLists'])->name('state-list');
        Route::post('city-list', [StoreController::class, 'cityLists'])->name('city-list');
        Route::post('users-list', [UsersController::class, 'getAllUsers'])->name('users-list');
        Route::post('notification-template-list', [NotificationTemplateController::class, 'select2List'])->name('notification-template-list');
        Route::post('stores-list', [StoreController::class, 'select2List'])->name('stores-list');
    });
});

// Ledger & Payments (Moved out of permission group for now)
Route::group(['middleware' => ['auth']], function () {
    Route::resource('payments', \App\Http\Controllers\PaymentController::class);
    Route::get('ledger', [\App\Http\Controllers\LedgerController::class, 'index'])->name('ledger.index');
    Route::get('ledger/{id}', [\App\Http\Controllers\LedgerController::class, 'show'])->name('ledger.show');
    Route::get('ledger/{id}/export/pdf', [\App\Http\Controllers\LedgerController::class, 'exportPdf'])->name('ledger.export_pdf');
    Route::get('ledger/{id}/export/excel', [\App\Http\Controllers\LedgerController::class, 'exportExcel'])->name('ledger.export_excel');
});

Route::post('import-stores', [StoreController::class, 'importStores'])->name('import-stores');
Route::get('export-stores', [StoreController::class, 'exportStores'])->name('export-stores');
Route::get('/settings/edit', [SettingController::class, 'edit'])->name('settings.edit');
Route::post('/settings/update', [SettingController::class, 'update'])->name('settings.update');

Route::view('json-to-dart', 'json-to-dart');