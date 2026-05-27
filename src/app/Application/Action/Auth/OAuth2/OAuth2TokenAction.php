<?php

declare(strict_types=1);

namespace App\Application\Action\Auth\OAuth2;

use App\Application\Transfers\Requests\Auth\OAuth2\OAuth2TokenRequestTransfer;
use App\Modules\MerchantApp\Commands\Handlers\FindMerchantAppOrFailByCriteriaHandler;
use App\Modules\MerchantApp\Enums\MerchantAppStatusEnum;
use App\Modules\MerchantApp\Exceptions\MerchantAppNotFoundException;
use App\Shared\Services\JwtService;

/**
 * Class OAuth2TokenAction
 *
 * @author Molozhenko
 * @package src/app/Application/Action/Auth/OAuth2/OAuth2TokenAction.php
 * @time 27.05.2026
 */
final readonly class OAuth2TokenAction
{
    /**
     * @param  JwtService                              $jwtService
     * @param  FindMerchantAppOrFailByCriteriaHandler  $findMerchantAppOrFailByCriteriaHandler
     */
    public function __construct(
        private JwtService                             $jwtService,
        private FindMerchantAppOrFailByCriteriaHandler $findMerchantAppOrFailByCriteriaHandler,
    ) {
    }

    /**
     * @param  OAuth2TokenRequestTransfer  $transfer
     *
     * @return array
     * @throws MerchantAppNotFoundException
     */
    public function execute(OAuth2TokenRequestTransfer $transfer): array
    {
        $app = $this->findMerchantAppOrFailByCriteriaHandler
            ->byClientId($transfer->clientId)
            ->byClientSecret($transfer->clientSecret)
            ->byStatus(MerchantAppStatusEnum::ACTIVE)
            ->execute();

        $ttl         = (int) config('jwt.ttl', 60);
        $accessToken = $this->encodeAccessToken(
           $app->getMerchantId(),$app->getId(), $ttl,
        );

        return [
            'access_token' => $accessToken,
            'token_type'   => 'bearer',
            'expires_in'   => $ttl * 60,
        ];
    }

    /**
     * @param  int  $merchantId
     * @param  int  $merchantAppId
     * @param  int  $ttl
     *
     * @return string
     */
    private function encodeAccessToken(int $merchantId, int $merchantAppId,int $ttl): string
    {
        return $this->jwtService->encode(
            claims: [
                'sub'         => $merchantId,
                'merchant_id' => $merchantId,
                'app_id'      => $merchantAppId,
            ],
            secret:     (string) config('jwt.secret'),
            ttlMinutes: $ttl,
            algo:       (string) config('jwt.algo', 'HS256'),
        );
    }
}
