<?php

declare(strict_types=1);

namespace App\Application\Http\Controllers\Merchant;

use App\Application\Action\Merchant\GetMerchantProfileAction;
use App\Application\Http\Requests\Merchant\GetMerchantProfileRequest;
use App\Modules\Merchant\Exceptions\MerchantNotFoundException;
use App\Shared\Controllers\AbstractApiController;
use Illuminate\Http\JsonResponse;

/**
 * Class GetMerchantProfileController
 *
 * @OA\Get(
 *     path="/api/merchant/profile",
 *     tags={"Merchant"},
 *     summary="Get authenticated merchant profile",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Merchant profile",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="merchant_uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *                 @OA\Property(property="business_name", type="string", example="Acme Store"),
 *                 @OA\Property(property="site_url", type="string", example="https://acme.com"),
 *                 @OA\Property(property="logo", type="string", nullable=true, example="https://cdn.uex.com/logos/1.png"),
 *                 @OA\Property(property="status", type="string", example="Approved")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 *
 * @author Molozhenko
 * @package src/app/Application/Http/Controllers/Merchant/GetMerchantProfileController.php
 * @time 27.05.2026
 */
final class GetMerchantProfileController extends AbstractApiController
{
    /**
     * @param  GetMerchantProfileAction  $action
     */
    public function __construct(
        private readonly GetMerchantProfileAction $action,
    ) {
    }

    /**
     * @param  GetMerchantProfileRequest  $request
     *
     * @return JsonResponse
     *
     * @throws MerchantNotFoundException
     */
    public function __invoke(GetMerchantProfileRequest $request): JsonResponse
    {
        return $this->success($this->action->execute($request->getTransfer()));
    }
}
