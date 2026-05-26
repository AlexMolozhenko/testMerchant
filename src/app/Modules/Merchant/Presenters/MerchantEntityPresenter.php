<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Presenters;

use App\Modules\Merchant\Entities\MerchantItemEntity;

/**
 * Class MerchantEntityPresenter
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Presenters/MerchantEntityPresenter.php
 * @time 26.05.2026
 */
final readonly class MerchantEntityPresenter
{
    /**
     * @param  MerchantItemEntity  $entity
     *
     * @return array<string, mixed>
     */
    public static function toArray(MerchantItemEntity $entity): array
    {
        return [
            'id'            => $entity->getId(),
            'merchant_uuid' => $entity->getMerchantUuid(),
            'business_name' => $entity->getBusinessName(),
            'site_url'      => $entity->getSiteUrl(),
            'logo'          => $entity->getLogo(),
            'status'        => $entity->getStatus(),
        ];
    }
}
