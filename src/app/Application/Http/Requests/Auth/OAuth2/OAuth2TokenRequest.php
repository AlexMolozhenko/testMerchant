<?php

declare(strict_types=1);

namespace App\Application\Http\Requests\Auth\OAuth2;

use App\Application\Transfers\Requests\Auth\OAuth2\OAuth2TokenRequestTransfer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class OAuth2TokenRequest
 *
 * @author Molozhenko
 * @package src/app/Application/Auth/OAuth2/Requests/OAuth2TokenRequest.php
 * @time 27.05.2026
 */
final class OAuth2TokenRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'client_id'     => 'required|string',
            'client_secret' => 'required|string',
        ];
    }

    /**
     * @return OAuth2TokenRequestTransfer
     */
    public function getTransfer(): OAuth2TokenRequestTransfer
    {
        return new OAuth2TokenRequestTransfer(
            clientId:     $this->string('client_id')->toString(),
            clientSecret: $this->string('client_secret')->toString(),
        );
    }
}
