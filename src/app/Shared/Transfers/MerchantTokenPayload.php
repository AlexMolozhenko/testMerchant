<?php

declare(strict_types=1);

namespace App\Shared\Transfers;

/**
 * Class MerchantTokenPayload
 *
 * @author Molozhenko
 * @package src/app/Shared/Transfers/MerchantTokenPayload.php
 * @time 27.05.2026
 */
final readonly class MerchantTokenPayload
{
    /**
     * @param  int     $userId
     * @param  int     $merchantId
     * @param  string  $merchantUuid
     */
    public function __construct(
        public int    $userId,
        public int    $merchantId,
        public string $merchantUuid,
    ) {
    }
}
