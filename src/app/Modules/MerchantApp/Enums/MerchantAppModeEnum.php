<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Enums;

/**
 * Enum MerchantAppModeEnum
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Enums/MerchantAppModeEnum.php
 * @time 26.05.2026
 */
enum MerchantAppModeEnum: string
{
    case LIVE = 'live';
    case TEST = 'test';
}
