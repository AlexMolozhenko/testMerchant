<?php

declare(strict_types=1);

namespace App\Application\Http\Controllers\Auth\Exchange;

use App\Application\Action\Auth\Exchange\ExchangeTokenAction;
use App\Application\Http\Requests\Auth\Exchange\ExchangeTokenRequest;
use App\Shared\Controllers\AbstractApiController;
use Illuminate\Http\JsonResponse;

/**
 * Class ExchangeTokenController
 *
 * @OA\Post(
 *      path="/api/merchant/auth/exchange",
 *      tags={"Auth"},
 *      summary="Exchange backend JWT for merchant JWT",
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\MediaType(
 *              mediaType="application/json",
 *              @OA\Schema(
 *                  required={"token"},
 *                  @OA\Property(
 *                      property="token",
 *                      type="string",
 *                      example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
 *                      description="Backend JWT issued by uexapp-backend"
 *                  )
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Merchant JWT issued successfully",
 *          @OA\JsonContent(
 *              @OA\Property(property="status", type="boolean", example=true),
 *              @OA\Property(property="data", type="object",
 *                  @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
 *                  @OA\Property(property="token_type", type="string", example="bearer"),
 *                  @OA\Property(property="expires_in", type="integer", example=3600)
 *              )
 *          )
 *      ),
 *      @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
 * )
 *
 * @author Molozhenko
 * @package src/app/Application/Http/Controllers/Auth/Exchange/ExchangeTokenController.php
 * @time 27.05.2026
 */
final class ExchangeTokenController extends AbstractApiController
{
    /**
     * @param  ExchangeTokenAction  $action
     */
    public function __construct(
        private readonly ExchangeTokenAction $action,
    ) {
    }

    /**
     * @param  ExchangeTokenRequest  $request
     *
     * @return JsonResponse
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function __invoke(ExchangeTokenRequest $request): JsonResponse
    {
        $result = $this->action->execute($request->getTransfer());

        return $this->success($result);
    }
}
