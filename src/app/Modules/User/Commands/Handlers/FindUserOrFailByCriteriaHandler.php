<?php

declare(strict_types=1);

namespace App\Modules\User\Commands\Handlers;

use App\Modules\User\Contracts\Repositories\UserContract;
use App\Modules\User\Entities\UserItemEntity;
use App\Modules\User\Exceptions\UserNotFoundException;
use App\Modules\User\Traits\Commands\UserCriteriaTrait;

/**
 * Class FindUserOrFailByCriteriaHandler
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Commands/Handlers/FindUserOrFailByCriteriaHandler.php
 * @time 27.05.2026
 */
final class FindUserOrFailByCriteriaHandler
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
     * @return UserItemEntity
     *
     * @throws UserNotFoundException
     */
    public function execute(): UserItemEntity
    {
        $entity = $this->contract->findByCriteria($this->getAndResetCriteria());

        if ($entity === null) {
            throw new UserNotFoundException();
        }

        return $entity;
    }
}
