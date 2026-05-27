<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Contracts\Repositories;

use App\Modules\MerchantApp\Entities\MerchantAppItemEntity;
use App\Modules\MerchantApp\Transfers\StoreMerchantAppTransfer;
use App\Shared\Contracts\Criteria\SharedCriterionContract;

/**
 * Interface MerchantAppContract
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Contracts/Repositories/MerchantAppContract.php
 * @time 26.05.2026
 */
interface MerchantAppContract
{
    /**
     * @param  SharedCriterionContract[]  $criteria
     *
     * @return MerchantAppItemEntity|null
     */
    public function findByCriteria(array $criteria): ?MerchantAppItemEntity;

    /**
     * @param  StoreMerchantAppTransfer  $transfer
     *
     * @return MerchantAppItemEntity
     */
    public function store(StoreMerchantAppTransfer $transfer): MerchantAppItemEntity;

    /**
     * @param  SharedCriterionContract[]  $criteria
     *
     * @return void
     */
    public function deleteByCriteria(array $criteria): void;
}
