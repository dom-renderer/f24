<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ApiController::class, 'login']);
Route::post('forgot-password', [ApiController::class, 'forgotPassword']);
Route::post('change-password', [ApiController::class, 'changePassword']);

Route::middleware(['auth:api', 'api-maintenance'])->group(function () {
    Route::get('stores', [ApiController::class, 'stores']);

    Route::post('device-token', [ApiController::class, 'deviceToken']);
    Route::post('remove-device-token', [ApiController::class, 'removeDeviceToken']);    
    Route::get('home-menus', [ApiController::class, 'homeMenus']);

    Route::get('users', [ApiController::class, 'users']);

    Route::get('cart', [ApiController::class, 'getCart']);
    Route::post('cart/add', [ApiController::class, 'addToCart']);
    Route::post('cart/update', [ApiController::class, 'updateCartItem']);
    Route::post('cart/remove', [ApiController::class, 'removeCartItem']);
    Route::post('cart/clear', [ApiController::class, 'clearCart']);
    Route::get('cart/summary', [ApiController::class, 'cartSummary']);

    Route::post('cart/other-item/add', [ApiController::class, 'addOtherItemToCart']);
    Route::post('cart/other-item/update', [ApiController::class, 'updateCartOtherItem']);
    Route::post('cart/other-item/remove', [ApiController::class, 'removeCartOtherItem']);

    Route::prefix('orders')->group(function () {
        Route::get('dashboard', [ApiController::class, 'dashboard']);
        
        Route::get('utencils', [ApiController::class, 'utencils']);
        Route::get('handling-instructions', [ApiController::class, 'handlingInstructions']);
        Route::get('categories', [ApiController::class, 'categories']);
        Route::get('products', [ApiController::class, 'products']);
        Route::get('packaging-materials', [ApiController::class, 'packagingMaterials']);
        Route::get('services', [ApiController::class, 'services']);
        Route::get('other-items', [ApiController::class, 'otherItems']);
        Route::get('settings', [ApiController::class, 'settings']);
        Route::get('reorder/{id}', [ApiController::class, 'reorder']);

        Route::post('place', [ApiController::class, 'placeOrder']);
        
        Route::get('/', [ApiController::class, 'getOrders']);
        Route::get('/{id}', [ApiController::class, 'getOrderDetail']);
        Route::put('/{id}', [ApiController::class, 'updateOrder']);
    });
});