<?php

declare(strict_types=1);

namespace App\Modules\User\Presenters;

use App\Modules\User\Entities\UserItemEntity;

/**
 * Class UserEntityPresenter
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Presenters/UserEntityPresenter.php
 * @time 26.05.2026
 */
final readonly class UserEntityPresenter
{
    /**
     * @param  UserItemEntity  $entity
     *
     * @return array<string, mixed>
     */
    public static function toArray(UserItemEntity $entity): array
    {
        return [
            'id'                 => $entity->getId(),
            'email'              => $entity->getEmail(),
            'sub_verified'       => $entity->isSubVerified(),
            'identity_verified'  => $entity->isIdentityVerified(),
            'applicant_id'       => $entity->getApplicantId(),
            'created_at'         => $entity->getCreatedAt()->toIso8601String(),
        ];
    }
}
