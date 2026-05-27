<?php

use App\Modules\Merchant\Providers\MerchantServiceProvider;
use App\Modules\MerchantApp\Providers\MerchantAppServiceProvider;
use App\Modules\User\Providers\UserServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    UserServiceProvider::class,
    MerchantServiceProvider::class,
    MerchantAppServiceProvider::class,
];
