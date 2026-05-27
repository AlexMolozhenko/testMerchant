<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Entities;

use App\Modules\MerchantApp\Enums\MerchantAppModeEnum;
use App\Modules\MerchantApp\Enums\MerchantAppStatusEnum;

/**
 * Class MerchantAppItemEntity
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Entities/MerchantAppItemEntity.php
 * @time 26.05.2026
 */
final readonly class MerchantAppItemEntity
{
    /**
     * @param  int                       $id
     * @param  int                       $merchantId
     * @param  string                    $clientId
     * @param  string                    $clientSecret
     * @param  string                    $name
     * @param  MerchantAppModeEnum       $mode
     * @param  array|null                $permissions
     * @param  int|null                  $rateLimitPerMinute
     * @param  MerchantAppStatusEnum     $status
     * @param  string|null               $lastUsedAt
     */
    public function __construct(
        private int                   $id,
        private int                   $merchantId,
        private string                $clientId,
        private string                $clientSecret,
        private string                $name,
        private MerchantAppModeEnum   $mode,
        private ?array                $permissions,
        private ?int                  $rateLimitPerMinute,
        private MerchantAppStatusEnum $status,
        private ?string               $lastUsedAt,
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMerchantId(): int
    {
        return $this->merchantId;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return MerchantAppModeEnum
     */
    public function getMode(): MerchantAppModeEnum
    {
        return $this->mode;
    }

    /**
     * @return array|null
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    /**
     * @return int|null
     */
    public function getRateLimitPerMinute(): ?int
    {
        return $this->rateLimitPerMinute;
    }

    /**
     * @return MerchantAppStatusEnum
     */
    public function getStatus(): MerchantAppStatusEnum
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getLastUsedAt(): ?string
    {
        return $this->lastUsedAt;
    }
}
