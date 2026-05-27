<?php

declare(strict_types=1);

namespace App\Application\Http\Requests;

use App\Shared\Transfers\MerchantTokenPayload;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AbstractMerchantRequest
 *
 * @author Molozhenko
 * @package src/app/Application/Http/Requests/AbstractMerchantRequest.php
 * @time 27.05.2026
 */
abstract class AbstractMerchantRequest extends FormRequest
{
    /**
     * @return MerchantTokenPayload
     */
    protected function merchant(): MerchantTokenPayload
    {
        /** @var MerchantTokenPayload */
        return $this->attributes->get('merchant');
    }
}
