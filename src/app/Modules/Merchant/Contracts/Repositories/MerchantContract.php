<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Contracts\Repositories;

use App\Modules\Merchant\Entities\MerchantItemEntity;
use App\Shared\Contracts\Criteria\SharedCriterionContract;

/**
 * Interface MerchantContract
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Contracts/Repositories/MerchantContract.php
 * @time 26.05.2026
 */
interface MerchantContract
{
    /**
     * @param  SharedCriterionContract[]  $criteria
     *
     * @return MerchantItemEntity|null
     */
    public function findByCriteria(array $criteria): ?MerchantItemEntity;
}
