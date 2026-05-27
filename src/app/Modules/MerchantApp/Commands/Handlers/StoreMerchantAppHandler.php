<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Commands\Handlers;

use App\Modules\MerchantApp\Contracts\Repositories\MerchantAppContract;
use App\Modules\MerchantApp\Entities\MerchantAppItemEntity;
use App\Modules\MerchantApp\Transfers\StoreMerchantAppTransfer;

/**
 * Class StoreMerchantAppHandler
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Commands/Handlers/StoreMerchantAppHandler.php
 * @time 26.05.2026
 */
final readonly class StoreMerchantAppHandler
{
    /**
     * @param  MerchantAppContract  $contract
     */
    public function __construct(
        private MerchantAppContract $contract,
    ) {
    }

    /**
     * @param  StoreMerchantAppTransfer  $transfer
     *
     * @return MerchantAppItemEntity
     */
    public function execute(StoreMerchantAppTransfer $transfer): MerchantAppItemEntity
    {
        return $this->contract->store($transfer);
    }
}
