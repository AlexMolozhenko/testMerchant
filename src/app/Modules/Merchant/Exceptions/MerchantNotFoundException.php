<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Exceptions;

use App\Shared\Exceptions\AppException;

/**
 * Class MerchantNotFoundException
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Exceptions/MerchantNotFoundException.php
 * @time 27.05.2026
 */
final class MerchantNotFoundException extends AppException
{
    /**
     * @var string
     */
    protected $message = 'Merchant not found.';

    /**
     * @var int
     */
    protected $code = 404;
}
