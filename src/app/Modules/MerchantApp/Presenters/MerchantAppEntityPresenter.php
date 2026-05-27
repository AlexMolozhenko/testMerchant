<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Presenters;

use App\Modules\MerchantApp\Entities\MerchantAppItemEntity;

/**
 * Class MerchantAppEntityPresenter
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Presenters/MerchantAppEntityPresenter.php
 * @time 26.05.2026
 */
final readonly class MerchantAppEntityPresenter
{
    /**
     * @param  MerchantAppItemEntity  $entity
     *
     * @return array<string, mixed>
     */
    public static function toArray(MerchantAppItemEntity $entity): array
    {
        return [
            'id'                   => $entity->getId(),
            'merchant_id'          => $entity->getMerchantId(),
            'client_id'            => $entity->getClientId(),
            'name'                 => $entity->getName(),
            'mode'                 => $entity->getMode()->value,
            'permissions'          => $entity->getPermissions(),
            'rate_limit_per_minute'=> $entity->getRateLimitPerMinute(),
            'status'               => $entity->getStatus()->value,
            'last_used_at'         => $entity->getLastUsedAt(),
        ];
    }
}
