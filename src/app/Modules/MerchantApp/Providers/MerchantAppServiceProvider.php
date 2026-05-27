<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Providers;

use App\Modules\MerchantApp\Contracts\Repositories\MerchantAppContract;
use App\Modules\MerchantApp\Repositories\MerchantAppRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Class MerchantAppServiceProvider
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Providers/MerchantAppServiceProvider.php
 * @time 26.05.2026
 */
final class MerchantAppServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(MerchantAppContract::class, MerchantAppRepository::class);
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Data/Migrations');
    }
}
