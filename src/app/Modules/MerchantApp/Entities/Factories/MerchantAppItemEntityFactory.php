<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Entities\Factories;

use App\Modules\MerchantApp\Entities\MerchantAppItemEntity;
use App\Modules\MerchantApp\Enums\MerchantAppModeEnum;
use App\Modules\MerchantApp\Enums\MerchantAppStatusEnum;
use App\Modules\MerchantApp\Models\MerchantApp;

/**
 * Class MerchantAppItemEntityFactory
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Entities/Factories/MerchantAppItemEntityFactory.php
 * @time 26.05.2026
 */
final readonly class MerchantAppItemEntityFactory
{
    /**
     * @param  MerchantApp  $model
     *
     * @return MerchantAppItemEntity
     */
    public static function makeFromModel(MerchantApp $model): MerchantAppItemEntity
    {
        return new MerchantAppItemEntity(
            id:                 $model->id,
            merchantId:         $model->merchant_id,
            clientId:           $model->client_id,
            clientSecret:       $model->client_secret,
            name:               $model->name,
            mode:               MerchantAppModeEnum::from($model->mode),
            permissions:        $model->permissions,
            rateLimitPerMinute: $model->rate_limit_per_minute,
            status:             MerchantAppStatusEnum::from($model->status),
            lastUsedAt:         $model->last_used_at,
        );
    }
}
