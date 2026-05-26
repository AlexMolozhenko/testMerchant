<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Modules\User\Contracts\Repositories\UserContract;
use App\Modules\User\Entities\Factories\UserItemEntityFactory;
use App\Modules\User\Entities\UserItemEntity;
use App\Modules\User\Models\User;
use App\Shared\Contracts\Criteria\SharedCriteriaApplierContract;
use App\Shared\Contracts\Criteria\SharedCriterionContract;

/**
 * Class UserRepository
 *
 * @psalm-suppress PossiblyUnusedMethod
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Repositories/UserRepository.php
 * @time 26.05.2026
 */
final readonly class UserRepository implements UserContract
{
    /**
     * @param  SharedCriteriaApplierContract  $applier
     */
    public function __construct(
        private SharedCriteriaApplierContract $applier,
    ) {
    }

    /**
     * @param  SharedCriterionContract[]  $criteria
     *
     * @return UserItemEntity|null
     */
    public function findByCriteria(array $criteria): ?UserItemEntity
    {
        /** @var User|null $model */
        $model = $this->applier->apply(User::query(), ...$criteria)->first();

        return $model ? UserItemEntityFactory::makeFromModel($model) : null;
    }
}
