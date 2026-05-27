<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Criteria;

use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MerchantAppByClientSecretCriteria
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Criteria/MerchantAppByClientSecretCriteria.php
 * @time 27.05.2026
 */
final readonly class MerchantAppByClientSecretCriteria implements SharedCriterionContract
{
    /**
     * @param  string  $clientSecret
     */
    public function __construct(private string $clientSecret)
    {
    }

    /**
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        return $query->where('client_secret', $this->clientSecret);
    }
}
