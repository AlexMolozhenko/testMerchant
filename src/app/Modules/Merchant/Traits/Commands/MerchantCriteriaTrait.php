<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Traits\Commands;

use App\Application\Shared\Criteria\UtilsCriteria;
use App\Modules\Merchant\Criteria\MerchantByIdCriteria;
use App\Modules\Merchant\Criteria\MerchantByStatusCriteria;
use App\Modules\Merchant\Criteria\MerchantByUserIdCriteria;
use App\Modules\Merchant\Enums\MerchantStatusEnum;

/**
 * Trait MerchantCriteriaTrait
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Traits/Commands/MerchantCriteriaTrait.php
 * @time 26.05.2026
 */
trait MerchantCriteriaTrait
{
    use UtilsCriteria;

    /**
     * @param  int  $id
     *
     * @return static
     */
    final public function byId(int $id): static
    {
        $this->criteria[] = new MerchantByIdCriteria($id);

        return $this;
    }

    /**
     * @param  int  $userId
     *
     * @return static
     */
    final public function byUserId(int $userId): static
    {
        $this->criteria[] = new MerchantByUserIdCriteria($userId);

        return $this;
    }

    /**
     * @param  MerchantStatusEnum  $status
     *
     * @return static
     */
    final public function byStatus(MerchantStatusEnum $status): static
    {
        $this->criteria[] = new MerchantByStatusCriteria($status);

        return $this;
    }
}
