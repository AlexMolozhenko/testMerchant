<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Criteria;

use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MerchantAppByMerchantIdCriteria
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Criteria/MerchantAppByMerchantIdCriteria.php
 * @time 26.05.2026
 */
final readonly class MerchantAppByMerchantIdCriteria implements SharedCriterionContract
{
    /**
     * @param  int  $merchantId
     */
    public function __construct(private int $merchantId)
    {
    }

    /**
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        return $query->where('merchant_id', $this->merchantId);
    }
}
