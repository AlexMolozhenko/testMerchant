<?php

declare(strict_types=1);

namespace App\Application\Http\Controllers\Auth\OAuth2;

use App\Application\Action\Auth\OAuth2\OAuth2TokenAction;
use App\Application\Http\Requests\Auth\OAuth2\OAuth2TokenRequest;
use App\Shared\Controllers\AbstractApiController;
use Illuminate\Http\JsonResponse;

/**
 * Class OAuth2TokenController
 *
 * @OA\Post(
 *      path="/api/merchant/oauth2/token",
 *      tags={"Auth"},
 *      summary="Issue merchant JWT via client credentials (server-to-server)",
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\MediaType(
 *              mediaType="application/json",
 *              @OA\Schema(
 *                  required={"client_id", "client_secret", "grant_type"},
 *                  @OA\Property(
 *                      property="client_id",
 *                      type="string",
 *                      example="550e8400-e29b-41d4-a716-446655440000",
 *                      description="Merchant app client_id from merchant_apps"
 *                  ),
 *                  @OA\Property(
 *                      property="client_secret",
 *                      type="string",
 *                      example="xK9mP2qR7tN4vL1wY8uZ3sA6bC0dE5fG",
 *                      description="Merchant app client_secret"
 *                  ),
 *                  @OA\Property(
 *                      property="grant_type",
 *                      type="string",
 *                      example="client_credentials",
 *                      description="OAuth2 grant type — must be client_credentials"
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
 * @package src/app/Application/Http/Controllers/Auth/OAuth2/OAuth2TokenController.php
 * @time 27.05.2026
 */
final class OAuth2TokenController extends AbstractApiController
{
    /**
     * @param  OAuth2TokenAction  $action
     */
    public function __construct(
        private readonly OAuth2TokenAction $action,
    ) {
    }

    /**
     * @param  OAuth2TokenRequest  $request
     *
     * @return JsonResponse
     * @throws \App\Modules\MerchantApp\Exceptions\MerchantAppNotFoundException
     * @throws \App\Modules\MerchantApp\Exceptions\MerchantAppSuspendedException
     */
    public function __invoke(OAuth2TokenRequest $request): JsonResponse
    {
        $result = $this->action->execute($request->getTransfer());

        return $this->success($result);
    }
}
