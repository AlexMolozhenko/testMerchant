<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Exceptions;

use App\Shared\Exceptions\AppException;

/**
 * Class MerchantAppSuspendedException
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Exceptions/MerchantAppSuspendedException.php
 * @time 27.05.2026
 */
final class MerchantAppSuspendedException extends AppException
{
    /**
     * @var string
     */
    protected $message = 'Merchant application is suspended.';

    /**
     * @var int
     */
    protected $code = 403;
}
