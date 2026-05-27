<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Enums;

/**
 * Enum MerchantStatusEnum
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Enums/MerchantStatusEnum.php
 * @time 27.05.2026
 */
enum MerchantStatusEnum: string
{
    case MODERATION  = 'Moderation';
    case DISAPPROVED = 'Disapproved';
    case APPROVED    = 'Approved';
}
