<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Commands\Handlers;

use App\Modules\MerchantApp\Contracts\Repositories\MerchantAppContract;
use App\Modules\MerchantApp\Entities\MerchantAppItemEntity;
use App\Modules\MerchantApp\Traits\Commands\MerchantAppCriteriaTrait;

/**
 * Class FindMerchantAppByCriteriaHandler
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Commands/Handlers/FindMerchantAppByCriteriaHandler.php
 * @time 26.05.2026
 */
final class FindMerchantAppByCriteriaHandler
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
     * @return MerchantAppItemEntity|null
     */
    public function execute(): ?MerchantAppItemEntity
    {
        return $this->contract->findByCriteria($this->getAndResetCriteria());
    }
}
