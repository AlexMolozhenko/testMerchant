<?php

declare(strict_types=1);

namespace App\Application\Transfers\Requests\Auth\Exchange;

/**
 * Class ExchangeTokenRequestTransfer
 *
 * @author Molozhenko
 * @package src/app/Application/Auth/Exchange/Transfers/ExchangeTokenRequestTransfer.php
 * @time 27.05.2026
 */
final readonly class ExchangeTokenRequestTransfer
{
    /**
     * @param  string  $token
     */
    public function __construct(
        public string $token,
    ) {
    }
}
