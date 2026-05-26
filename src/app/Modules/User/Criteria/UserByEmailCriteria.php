<?php

declare(strict_types=1);

namespace App\Modules\User\Criteria;

use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class UserByEmailCriteria
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Criteria/UserByEmailCriteria.php
 * @time 26.05.2026
 */
final readonly class UserByEmailCriteria implements SharedCriterionContract
{
    /**
     * @param  string  $email
     */
    public function __construct(private string $email)
    {
    }

    /**
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        return $query->where('email', $this->email);
    }
}
