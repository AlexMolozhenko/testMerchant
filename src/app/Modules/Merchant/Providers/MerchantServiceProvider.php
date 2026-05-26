<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Providers;

use App\Modules\Merchant\Contracts\Repositories\MerchantContract;
use App\Modules\Merchant\Repositories\MerchantRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Class MerchantServiceProvider
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Providers/MerchantServiceProvider.php
 * @time 26.05.2026
 */
final class MerchantServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(MerchantContract::class, MerchantRepository::class);
    }
}
