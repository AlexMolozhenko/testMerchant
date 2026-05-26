<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\Contracts\Criteria\SharedCriteriaApplierContract;
use App\Shared\Criteria\SharedCriteriaApplier;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 *
 * @author Molozhenko
 * @package src/app/Providers/AppServiceProvider.php
 * @time 26.05.2026
 */
final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SharedCriteriaApplierContract::class, SharedCriteriaApplier::class);
    }

    public function boot(): void
    {
        //
    }
}
