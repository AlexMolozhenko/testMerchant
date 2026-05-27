<?php

declare(strict_types=1);

namespace App\Application\Transfers\Requests\Merchant;

/**
 * Class GetMerchantProfileRequestTransfer
 *
 * @author Molozhenko
 * @package src/app/Application/Transfers/Requests/Merchant/GetMerchantProfileRequestTransfer.php
 * @time 27.05.2026
 */
final readonly class GetMerchantProfileRequestTransfer
{
    /**
     * @param  int  $merchantId
     */
    public function __construct(
        public int $merchantId,
    ) {
    }
}
