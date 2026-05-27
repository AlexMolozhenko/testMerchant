<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Exceptions;

use App\Shared\Exceptions\AppException;

/**
 * Class MerchantNotActiveException
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Exceptions/MerchantNotActiveException.php
 * @time 27.05.2026
 */
final class MerchantNotActiveException extends AppException
{
    /**
     * @var string
     */
    protected $message = 'Merchant account is not active.';

    /**
     * @var int
     */
    protected $code = 403;
}
