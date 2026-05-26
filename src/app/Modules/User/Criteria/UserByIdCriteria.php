<?php

declare(strict_types=1);

namespace App\Modules\User\Criteria;

use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class UserByIdCriteria
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Criteria/UserByIdCriteria.php
 * @time 26.05.2026
 */
final readonly class UserByIdCriteria implements SharedCriterionContract
{
    /**
     * @param  int  $id
     */
    public function __construct(private int $id)
    {
    }

    /**
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        return $query->where('id', $this->id);
    }
}
