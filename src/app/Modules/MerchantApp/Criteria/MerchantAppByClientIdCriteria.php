<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Criteria;

use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MerchantAppByClientIdCriteria
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Criteria/MerchantAppByClientIdCriteria.php
 * @time 26.05.2026
 */
final readonly class MerchantAppByClientIdCriteria implements SharedCriterionContract
{
    /**
     * @param  string  $clientId
     */
    public function __construct(private string $clientId)
    {
    }

    /**
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        return $query->where('client_id', $this->clientId);
    }
}
