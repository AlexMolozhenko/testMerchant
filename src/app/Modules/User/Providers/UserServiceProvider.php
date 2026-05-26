<?php

declare(strict_types=1);

namespace App\Modules\User\Providers;

use App\Modules\User\Contracts\Repositories\UserContract;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Class UserServiceProvider
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Providers/UserServiceProvider.php
 * @time 26.05.2026
 */
final class UserServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(UserContract::class, UserRepository::class);
    }
}
