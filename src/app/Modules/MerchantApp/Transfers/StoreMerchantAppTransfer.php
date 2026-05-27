<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Transfers;

use App\Modules\MerchantApp\Enums\MerchantAppModeEnum;

/**
 * Class StoreMerchantAppTransfer
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Transfers/StoreMerchantAppTransfer.php
 * @time 26.05.2026
 */
final readonly class StoreMerchantAppTransfer
{
    /**
     * @param  int                  $merchantId
     * @param  string               $name
     * @param  MerchantAppModeEnum  $mode
     * @param  array|null           $permissions
     * @param  int|null             $rateLimitPerMinute
     */
    public function __construct(
        public int                $merchantId,
        public string             $name,
        public MerchantAppModeEnum $mode,
        public ?array             $permissions,
        public ?int               $rateLimitPerMinute,
    ) {
    }
}
