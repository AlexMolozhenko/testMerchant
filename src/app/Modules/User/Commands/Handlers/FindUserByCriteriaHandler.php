<?php

declare(strict_types=1);

namespace App\Modules\User\Commands\Handlers;

use App\Modules\User\Contracts\Repositories\UserContract;
use App\Modules\User\Entities\UserItemEntity;
use App\Modules\User\Traits\Commands\UserCriteriaTrait;

/**
 * Class FindUserByCriteriaHandler
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Commands/Handlers/FindUserByCriteriaHandler.php
 * @time 26.05.2026
 */
final class FindUserByCriteriaHandler
{
    use UserCriteriaTrait;

    /**
     * @param  UserContract  $contract
     */
    public function __construct(
        private readonly UserContract $contract,
    ) {
    }

    /**
     * @return UserItemEntity|null
     */
    public function execute(): ?UserItemEntity
    {
        return $this->contract->findByCriteria($this->getAndResetCriteria());
    }
}
