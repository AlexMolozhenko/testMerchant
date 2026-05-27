<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Criteria;

use App\Modules\Merchant\Enums\MerchantStatusEnum;
use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MerchantByStatusCriteria
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Criteria/MerchantByStatusCriteria.php
 * @time 27.05.2026
 */
final readonly class MerchantByStatusCriteria implements SharedCriterionContract
{
    /**
     * @param  MerchantStatusEnum  $status
     */
    public function __construct(private MerchantStatusEnum $status)
    {
    }

    /**
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        return $query->where('status', $this->status->value);
    }
}
