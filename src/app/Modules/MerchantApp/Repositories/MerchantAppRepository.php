<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Repositories;

use App\Modules\MerchantApp\Contracts\Repositories\MerchantAppContract;
use App\Modules\MerchantApp\Entities\Factories\MerchantAppItemEntityFactory;
use App\Modules\MerchantApp\Entities\MerchantAppItemEntity;
use App\Modules\MerchantApp\Enums\MerchantAppStatusEnum;
use App\Modules\MerchantApp\Models\MerchantApp;
use App\Modules\MerchantApp\Transfers\StoreMerchantAppTransfer;
use App\Shared\Contracts\Criteria\SharedCriteriaApplierContract;
use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Support\Str;

/**
 * Class MerchantAppRepository
 *
 * @psalm-suppress PossiblyUnusedMethod
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Repositories/MerchantAppRepository.php
 * @time 26.05.2026
 */
final readonly class MerchantAppRepository implements MerchantAppContract
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
     * @return MerchantAppItemEntity|null
     */
    public function findByCriteria(array $criteria): ?MerchantAppItemEntity
    {
        /** @var MerchantApp|null $model */
        $model = $this->applier->apply(MerchantApp::query(), ...$criteria)->first();

        return $model ? MerchantAppItemEntityFactory::makeFromModel($model) : null;
    }

    /**
     * @param  StoreMerchantAppTransfer  $transfer
     *
     * @return MerchantAppItemEntity
     */
    public function store(StoreMerchantAppTransfer $transfer): MerchantAppItemEntity
    {
        $model = new MerchantApp();

        $model->merchant_id          = $transfer->merchantId;
        $model->client_id            = Str::uuid()->toString();
        $model->client_secret        = Str::random(64);
        $model->name                 = $transfer->name;
        $model->mode                 = $transfer->mode->value;
        $model->permissions          = $transfer->permissions;
        $model->rate_limit_per_minute = $transfer->rateLimitPerMinute;
        $model->status               = MerchantAppStatusEnum::ACTIVE->value;

        $model->save();

        return MerchantAppItemEntityFactory::makeFromModel($model);
    }

    /**
     * @param  SharedCriterionContract[]  $criteria
     *
     * @return void
     */
    public function deleteByCriteria(array $criteria): void
    {
        $this->applier->apply(MerchantApp::query(), ...$criteria)->delete();
    }
}
