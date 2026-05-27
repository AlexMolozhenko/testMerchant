<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

use App\Shared\Exceptions\AppException;

/**
 * Class UserNotFoundException
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Exceptions/UserNotFoundException.php
 * @time 27.05.2026
 */
final class UserNotFoundException extends AppException
{
    /**
     * @var string
     */
    protected $message = 'User not found.';

    /**
     * @var int
     */
    protected $code = 404;
}
