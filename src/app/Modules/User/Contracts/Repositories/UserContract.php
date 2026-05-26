<?php

declare(strict_types=1);

namespace App\Modules\User\Contracts\Repositories;

use App\Modules\User\Entities\UserItemEntity;
use App\Shared\Contracts\Criteria\SharedCriterionContract;

/**
 * Interface UserContract
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Contracts/Repositories/UserContract.php
 * @time 26.05.2026
 */
interface UserContract
{
    /**
     * @param  SharedCriterionContract[]  $criteria
     *
     * @return UserItemEntity|null
     */
    public function findByCriteria(array $criteria): ?UserItemEntity;
}
