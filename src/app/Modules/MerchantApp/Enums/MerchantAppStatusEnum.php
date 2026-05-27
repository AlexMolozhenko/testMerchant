<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Enums;

/**
 * Enum MerchantAppStatusEnum
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Enums/MerchantAppStatusEnum.php
 * @time 26.05.2026
 */
enum MerchantAppStatusEnum: string
{
    case ACTIVE    = 'active';
    case SUSPENDED = 'suspended';
}
