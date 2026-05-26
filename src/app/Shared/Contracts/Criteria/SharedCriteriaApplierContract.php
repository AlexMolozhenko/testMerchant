<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Criteria;

use Illuminate\Database\Eloquent\Builder;

/**
 * Interface SharedCriteriaApplierContract
 *
 * @author Molozhenko
 * @package src/app/Shared/Contracts/Criteria/SharedCriteriaApplierContract.php
 * @time 26.05.2026
 */
interface SharedCriteriaApplierContract
{
    public function apply(Builder $query, SharedCriterionContract ...$criteria): Builder;
}
