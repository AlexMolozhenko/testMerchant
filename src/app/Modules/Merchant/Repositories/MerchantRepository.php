<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

use App\Modules\Merchant\Contracts\Repositories\MerchantContract;
use App\Modules\Merchant\Entities\Factories\MerchantItemEntityFactory;
use App\Modules\Merchant\Entities\MerchantItemEntity;
use App\Modules\Merchant\Models\Merchant;
use App\Shared\Contracts\Criteria\SharedCriteriaApplierContract;
use App\Shared\Contracts\Criteria\SharedCriterionContract;

/**
 * Class MerchantRepository
 *
 * @psalm-suppress PossiblyUnusedMethod
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Repositories/MerchantRepository.php
 * @time 26.05.2026
 */
final readonly class MerchantRepository implements MerchantContract
{
    /**
     * @param  SharedCriteriaApplierContract  $applier
     */
    public function __construct(
        private SharedCriteriaApplierContract $applier,
    ) {
    }

    /**
     * @param  SharedCriterionContract[]  $criteria
     *
     * @return MerchantItemEntity|null
     */
    public function findByCriteria(array $criteria): ?MerchantItemEntity
    {
        /** @var Merchant|null $model */
        $model = $this->applier->apply(Merchant::query(), ...$criteria)->first();

        return $model ? MerchantItemEntityFactory::makeFromModel($model) : null;
    }
}
