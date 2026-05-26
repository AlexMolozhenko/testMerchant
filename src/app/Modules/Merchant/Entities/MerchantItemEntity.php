<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Entities;

use App\Modules\Merchant\Presenters\MerchantEntityPresenter;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Class MerchantItemEntity
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Entities/MerchantItemEntity.php
 * @time 26.05.2026
 */
final readonly class MerchantItemEntity implements Arrayable
{
    /**
     * @param  int  $id
     * @param  int  $userId
     * @param  string  $merchantUuid
     * @param  string  $businessName
     * @param  string  $siteUrl
     * @param  string|null  $logo
     * @param  string  $status
     */
    public function __construct(
        private int $id,
        private int $userId,
        private string $merchantUuid,
        private string $businessName,
        private string $siteUrl,
        private ?string $logo,
        private string $status,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return MerchantEntityPresenter::toArray($this);
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
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getMerchantUuid(): string
    {
        return $this->merchantUuid;
    }

    /**
     * @return string
     */
    public function getBusinessName(): string
    {
        return $this->businessName;
    }

    /**
     * @return string
     */
    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }

    /**
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}
