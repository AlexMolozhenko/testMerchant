<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Criteria;

use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MerchantByUserIdCriteria
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Criteria/MerchantByUserIdCriteria.php
 * @time 26.05.2026
 */
final readonly class MerchantByUserIdCriteria implements SharedCriterionContract
{
    /**
     * @param  int  $userId
     */
    public function __construct(private int $userId)
    {
    }

    /**
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        return $query->where('user_id', $this->userId);
    }
}
