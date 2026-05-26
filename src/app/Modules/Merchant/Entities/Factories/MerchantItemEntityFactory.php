<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Entities\Factories;

use App\Modules\Merchant\Entities\MerchantItemEntity;
use App\Modules\Merchant\Models\Merchant;

/**
 * Class MerchantItemEntityFactory
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Entities/Factories/MerchantItemEntityFactory.php
 * @time 26.05.2026
 */
final readonly class MerchantItemEntityFactory
{
    /**
     * @param  Merchant  $model
     *
     * @return MerchantItemEntity
     */
    public static function makeFromModel(Merchant $model): MerchantItemEntity
    {
        return new MerchantItemEntity(
            id:           $model->id,
            userId:       $model->user_id,
            merchantUuid: $model->merchant_uuid,
            businessName: $model->business_name,
            siteUrl:      $model->site_url,
            logo:         $model->logo,
            status:       $model->status,
        );
    }
}
