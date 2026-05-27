<?php

declare(strict_types=1);

namespace App\Application\Http\Requests\Auth\Exchange;

use App\Application\Transfers\Requests\Auth\Exchange\ExchangeTokenRequestTransfer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ExchangeTokenRequest
 *
 * @author Molozhenko
 * @package src/app/Application/Auth/Exchange/Requests/ExchangeTokenRequest.php
 * @time 27.05.2026
 */
final class ExchangeTokenRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string',
        ];
    }

    /**
     * @return ExchangeTokenRequestTransfer
     */
    public function getTransfer(): ExchangeTokenRequestTransfer
    {
        return new ExchangeTokenRequestTransfer(
            token: $this->string('token')->toString(),
        );
    }
}
