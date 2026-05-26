<?php

declare(strict_types=1);

namespace App\Modules\User\Entities;

use App\Modules\User\Presenters\UserEntityPresenter;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * Class UserItemEntity
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Entities/UserItemEntity.php
 * @time 26.05.2026
 */
final readonly class UserItemEntity implements Arrayable
{
    /**
     * @param  int  $id
     * @param  string  $email
     * @param  bool  $subVerified
     * @param  bool  $identityVerified
     * @param  string|null  $applicantId
     * @param  string|null  $google2faSecret
     * @param  Carbon  $createdAt
     */
    public function __construct(
        private int $id,
        private string $email,
        private bool $subVerified,
        private bool $identityVerified,
        private ?string $applicantId,
        private ?string $google2faSecret,
        private Carbon $createdAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return UserEntityPresenter::toArray($this);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return bool
     */
    public function isSubVerified(): bool
    {
        return $this->subVerified;
    }

    /**
     * @return bool
     */
    public function isIdentityVerified(): bool
    {
        return $this->identityVerified;
    }

    /**
     * @return string|null
     */
    public function getApplicantId(): ?string
    {
        return $this->applicantId;
    }

    /**
     * @return string|null
     */
    public function getGoogle2faSecret(): ?string
    {
        return $this->google2faSecret;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }
}
