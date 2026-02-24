<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderProductPriceManagement;
use App\Models\OrderCharge;
use App\Observers\OrderObserver;
use App\Observers\OrderItemObserver;
use App\Observers\OrderProductPriceManagementObserver;
use App\Observers\OrderChargeObserver;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        \Illuminate\Pagination\Paginator::useBootstrap();        

        if (!defined('APP_NAME')) {
            define('APP_NAME', 'Farki');
        }

        Scramble::extendOpenApi(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });

        Order::observe(OrderObserver::class);
        OrderItem::observe(OrderItemObserver::class);
        OrderProductPriceManagement::observe(OrderProductPriceManagementObserver::class);
        OrderCharge::observe(OrderChargeObserver::class);
    }
}
