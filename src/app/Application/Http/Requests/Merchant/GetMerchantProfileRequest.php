<?php

declare(strict_types=1);

namespace App\Application\Http\Requests\Merchant;

use App\Application\Http\Requests\AbstractMerchantRequest;
use App\Application\Transfers\Requests\Merchant\GetMerchantProfileRequestTransfer;

/**
 * Class GetMerchantProfileRequest
 *
 * @author Molozhenko
 * @package src/app/Application/Http/Requests/Merchant/GetMerchantProfileRequest.php
 * @time 27.05.2026
 */
final class GetMerchantProfileRequest extends AbstractMerchantRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return GetMerchantProfileRequestTransfer
     */
    public function getTransfer(): GetMerchantProfileRequestTransfer
    {
        return new GetMerchantProfileRequestTransfer(
            merchantId: $this->merchant()->merchantId,
        );
    }
}
