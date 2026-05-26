<?php

use App\Modules\Merchant\Providers\MerchantServiceProvider;
use App\Modules\User\Providers\UserServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    UserServiceProvider::class,
    MerchantServiceProvider::class,
];
