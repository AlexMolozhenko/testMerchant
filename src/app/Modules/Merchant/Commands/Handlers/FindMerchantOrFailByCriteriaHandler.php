<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Commands\Handlers;

use App\Modules\Merchant\Contracts\Repositories\MerchantContract;
use App\Modules\Merchant\Entities\MerchantItemEntity;
use App\Modules\Merchant\Exceptions\MerchantNotFoundException;
use App\Modules\Merchant\Traits\Commands\MerchantCriteriaTrait;

/**
 * Class FindMerchantByCriteriaHandler
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Commands/Handlers/FindMerchantByCriteriaHandler.php
 * @time 26.05.2026
 */
final class FindMerchantOrFailByCriteriaHandler
{
    use MerchantCriteriaTrait;

    /**
     * @param  MerchantContract  $contract
     */
    public function __construct(
        private readonly MerchantContract $contract,
    ) {
    }

    /**
     * @return MerchantItemEntity
     * @throws MerchantNotFoundException
     */
    public function execute(): MerchantItemEntity
    {
        $entity = $this->contract->findByCriteria($this->getAndResetCriteria());

        if ($entity === null) {
            throw new MerchantNotFoundException();
        }

        return $entity;
    }
}
