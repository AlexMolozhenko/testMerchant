<?php

declare(strict_types=1);

namespace App\Application\Action\Auth\Exchange;

use App\Application\Transfers\Requests\Auth\Exchange\ExchangeTokenRequestTransfer;
use App\Modules\Merchant\Commands\Handlers\FindMerchantByCriteriaHandler;
use App\Modules\Merchant\Commands\Handlers\FindMerchantOrFailByCriteriaHandler;
use App\Modules\Merchant\Enums\MerchantStatusEnum;
use App\Modules\Merchant\Exceptions\MerchantNotFoundException;
use App\Modules\User\Commands\Handlers\FindUserOrFailByCriteriaHandler;
use App\Shared\Exceptions\RuntimeException;
use App\Shared\Services\JwtService;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;

/**
 * Class ExchangeTokenAction
 *
 * @author Molozhenko
 * @package src/app/Application/Auth/Exchange/Actions/ExchangeTokenAction.php
 * @time 27.05.2026
 */
final readonly class ExchangeTokenAction
{
    /**
     * @param  JwtService  $jwtService
     * @param  FindUserOrFailByCriteriaHandler  $findUserOrFailByCriteriaHandler
     * @param  FindMerchantOrFailByCriteriaHandler  $findMerchantOrFailByCriteriaHandler
     */
    public function __construct(
        private JwtService                      $jwtService,
        private FindUserOrFailByCriteriaHandler $findUserOrFailByCriteriaHandler,
        private FindMerchantOrFailByCriteriaHandler   $findMerchantOrFailByCriteriaHandler,
    ) {
    }

    /**
     * @param  ExchangeTokenRequestTransfer  $transfer
     *
     * @return array
     * @throws MerchantNotFoundException
     * @throws RuntimeException
     * @throws \App\Modules\User\Exceptions\UserNotFoundException
     */
    public function execute(ExchangeTokenRequestTransfer $transfer): array
    {
        $backendToken = $this->decodeBackendToken($transfer->token);

        $userId = (int) $backendToken->claims()->get('sub');

        $user = $this->findUserOrFailByCriteriaHandler->byId($userId)->execute();
        $merchant = $this->findMerchantOrFailByCriteriaHandler
            ->byUserId($user->getId())
            ->byStatus(MerchantStatusEnum::APPROVED)
            ->execute();

        $ttl         = (int) config('jwt.ttl', 60);
        $accessToken = $this->encodeAccessToken($user->getId(), $merchant->getId(), $merchant->getMerchantUuid(), $ttl);

        return [
            'access_token' => $accessToken,
            'token_type'   => 'bearer',
            'expires_in'   => $ttl * 60,
        ];
    }

    /**
     * @param  string  $token
     *
     * @return Plain
     *
     * @throws RuntimeException
     */
    private function decodeBackendToken(string $token): Plain
    {
        try {
            return $this->jwtService->decode(
                tokenString: $token,
                secret:      (string) config('jwt.backend_secret'),
                algo:        (string) config('jwt.backend_algo', 'HS256'),
            );
        } catch (RequiredConstraintsViolated) {
            throw new RuntimeException('Invalid backend token.', 401);
        }
    }

    /**
     * @param  int  $userId
     * @param  int  $merchantId
     * @param  string  $merchantUuId
     * @param  int  $ttl
     *
     * @return string
     */
    private function encodeAccessToken(int $userId,int $merchantId, string $merchantUuId,int $ttl): string
    {
        return $this->jwtService->encode(
            claims: [
                'sub'           => $userId,
                'merchant_id'   => $merchantId,
                'merchant_uuid' => $merchantUuId,
            ],
            secret:     (string) config('jwt.secret'),
            ttlMinutes: $ttl,
            algo:       (string) config('jwt.algo', 'HS256'),
        );
    }
}
