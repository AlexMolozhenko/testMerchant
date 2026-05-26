<?php

declare(strict_types=1);

namespace App\Shared\Criteria;

use App\Shared\Contracts\Criteria\SharedCriteriaApplierContract;
use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class SharedCriteriaApplier
 *
 * @author Molozhenko
 * @package src/app/Shared/Criteria/SharedCriteriaApplier.php
 * @time 26.05.2026
 */
final readonly class SharedCriteriaApplier implements SharedCriteriaApplierContract
{
    /**
     * @param  Builder  $query
     * @param  SharedCriterionContract  ...$criteria
     *
     * @return Builder
     */
    public function apply(Builder $query, SharedCriterionContract ...$criteria): Builder
    {
        foreach ($criteria as $criterion) {
            $query = $criterion->apply($query);
        }

        return $query;
    }
}
