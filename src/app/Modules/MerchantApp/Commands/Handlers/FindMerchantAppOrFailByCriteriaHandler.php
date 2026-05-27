<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Commands\Handlers;

use App\Modules\MerchantApp\Contracts\Repositories\MerchantAppContract;
use App\Modules\MerchantApp\Entities\MerchantAppItemEntity;
use App\Modules\MerchantApp\Exceptions\MerchantAppNotFoundException;
use App\Modules\MerchantApp\Traits\Commands\MerchantAppCriteriaTrait;

/**
 * Class FindMerchantAppOrFailByCriteriaHandler
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Commands/Handlers/FindMerchantAppOrFailByCriteriaHandler.php
 * @time 27.05.2026
 */
final class FindMerchantAppOrFailByCriteriaHandler
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
     * @return MerchantAppItemEntity
     *
     * @throws MerchantAppNotFoundException
     */
    public function execute(): MerchantAppItemEntity
    {
        $entity = $this->contract->findByCriteria($this->getAndResetCriteria());

        if ($entity === null) {
            throw new MerchantAppNotFoundException();
        }

        return $entity;
    }
}
