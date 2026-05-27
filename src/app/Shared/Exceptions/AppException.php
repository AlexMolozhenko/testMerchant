<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Exception;

/**
 * Class AppException
 *
 * @author Molozhenko
 * @package src/app/Shared/Exceptions/AppException.php
 * @time 27.05.2026
 */
class AppException extends Exception
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * HTTP status code — taken from the $code descendant (404, 403, 422, etc.)
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->code > 0 ? $this->code : 422;
    }
}
