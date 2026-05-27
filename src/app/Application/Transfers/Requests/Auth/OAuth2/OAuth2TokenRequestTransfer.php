<?php

declare(strict_types=1);

namespace App\Application\Transfers\Requests\Auth\OAuth2;

/**
 * Class OAuth2TokenRequestTransfer
 *
 * @author Molozhenko
 * @package src/app/Application/Auth/OAuth2/Transfers/OAuth2TokenRequestTransfer.php
 * @time 27.05.2026
 */
final readonly class OAuth2TokenRequestTransfer
{
    /**
     * @param  string  $clientId
     * @param  string  $clientSecret
     */
    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {
    }
}
