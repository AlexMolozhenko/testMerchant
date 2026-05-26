<?php

declare(strict_types=1);

namespace App\Modules\User\Entities\Factories;

use App\Modules\User\Entities\UserItemEntity;
use App\Modules\User\Models\User;

/**
 * Class UserItemEntityFactory
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Entities/Factories/UserItemEntityFactory.php
 * @time 26.05.2026
 */
final readonly class UserItemEntityFactory
{
    /**
     * @param  User  $model
     *
     * @return UserItemEntity
     */
    public static function makeFromModel(User $model): UserItemEntity
    {
        return new UserItemEntity(
            id:               $model->id,
            email:            $model->email,
            subVerified:      $model->sub_verified,
            identityVerified: $model->identity_verified,
            applicantId:      $model->applicant_id,
            google2faSecret:  $model->google2fa_secret,
            createdAt:        $model->created_at,
        );
    }
}
