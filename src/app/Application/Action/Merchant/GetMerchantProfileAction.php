<?php

declare(strict_types=1);

namespace App\Application\Action\Merchant;

use App\Application\Transfers\Requests\Merchant\GetMerchantProfileRequestTransfer;
use App\Modules\Merchant\Commands\Handlers\FindMerchantOrFailByCriteriaHandler;
use App\Modules\Merchant\Exceptions\MerchantNotFoundException;

/**
 * Class GetMerchantProfileAction
 *
 * @author Molozhenko
 * @package src/app/Application/Action/Merchant/GetMerchantProfileAction.php
 * @time 27.05.2026
 */
final readonly class GetMerchantProfileAction
{
    /**
     * @param  FindMerchantOrFailByCriteriaHandler  $findMerchantOrFailByCriteriaHandler
     */
    public function __construct(
        private FindMerchantOrFailByCriteriaHandler $findMerchantOrFailByCriteriaHandler,
    ) {
    }

    /**
     * @param  GetMerchantProfileRequestTransfer  $transfer
     *
     * @return array<string, mixed>
     *
     * @throws MerchantNotFoundException
     */
    public function execute(GetMerchantProfileRequestTransfer $transfer): array
    {
        return $this->findMerchantOrFailByCriteriaHandler
            ->byId($transfer->merchantId)
            ->execute()
            ->toArray();
    }
}
