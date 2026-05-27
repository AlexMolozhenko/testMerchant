<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Commands\Handlers;

use App\Modules\MerchantApp\Contracts\Repositories\MerchantAppContract;
use App\Modules\MerchantApp\Traits\Commands\MerchantAppCriteriaTrait;

/**
 * Class DeleteMerchantAppHandler
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Commands/Handlers/DeleteMerchantAppHandler.php
 * @time 26.05.2026
 */
final class DeleteMerchantAppHandler
{
    use MerchantAppCriteriaTrait;

    /**
     * @param  MerchantAppContract  $contract
     */
    public function __construct(
        private readonly MerchantAppContract $contract,
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->contract->deleteByCriteria($this->getAndResetCriteria());
    }
}
