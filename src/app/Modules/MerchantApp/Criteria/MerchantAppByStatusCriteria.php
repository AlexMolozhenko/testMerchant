<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Criteria;

use App\Modules\MerchantApp\Enums\MerchantAppStatusEnum;
use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MerchantAppByStatusCriteria
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Criteria/MerchantAppByStatusCriteria.php
 * @time 27.05.2026
 */
final readonly class MerchantAppByStatusCriteria implements SharedCriterionContract
{
    /**
     * @param  MerchantAppStatusEnum  $status
     */
    public function __construct(private MerchantAppStatusEnum $status)
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
