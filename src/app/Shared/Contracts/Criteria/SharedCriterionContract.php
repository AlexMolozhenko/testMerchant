<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Criteria;

use Illuminate\Database\Eloquent\Builder;

/**
 * Interface SharedCriterionContract
 *
 * @author Molozhenko
 * @package src/app/Shared/Contracts/Criteria/SharedCriterionContract.php
 * @time 26.05.2026
 */
interface SharedCriterionContract
{
    /**
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function apply(Builder $query): Builder;
}
