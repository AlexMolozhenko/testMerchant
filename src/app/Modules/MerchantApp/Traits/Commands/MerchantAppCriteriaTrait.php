<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Traits\Commands;

use App\Application\Shared\Criteria\UtilsCriteria;
use App\Modules\MerchantApp\Criteria\MerchantAppByClientIdCriteria;
use App\Modules\MerchantApp\Criteria\MerchantAppByIdCriteria;
use App\Modules\MerchantApp\Criteria\MerchantAppByMerchantIdCriteria;

/**
 * Trait MerchantAppCriteriaTrait
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Traits/Commands/MerchantAppCriteriaTrait.php
 * @time 26.05.2026
 */
trait MerchantAppCriteriaTrait
{
    use UtilsCriteria;

    /**
     * @param  int  $id
     *
     * @return static
     */
    final public function byId(int $id): static
    {
        $this->criteria[] = new MerchantAppByIdCriteria($id);

        return $this;
    }

    /**
     * @param  string  $clientId
     *
     * @return static
     */
    final public function byClientId(string $clientId): static
    {
        $this->criteria[] = new MerchantAppByClientIdCriteria($clientId);

        return $this;
    }

    /**
     * @param  int  $merchantId
     *
     * @return static
     */
    final public function byMerchantId(int $merchantId): static
    {
        $this->criteria[] = new MerchantAppByMerchantIdCriteria($merchantId);

        return $this;
    }
}
