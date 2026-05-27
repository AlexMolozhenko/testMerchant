<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Exceptions;

use App\Shared\Exceptions\AppException;

/**
 * Class MerchantAppNotFoundException
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Exceptions/MerchantAppNotFoundException.php
 * @time 27.05.2026
 */
final class MerchantAppNotFoundException extends AppException
{
    /**
     * @var string
     */
    protected $message = 'Merchant application not found.';

    /**
     * @var int
     */
    protected $code = 404;
}
