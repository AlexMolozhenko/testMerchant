<?php

declare(strict_types=1);

namespace App\Application\Http\Middleware\Auth;

use App\Shared\Services\JwtService;
use App\Shared\Transfers\MerchantTokenPayload;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MerchantAuthMiddleware
 *
 * @author Molozhenko
 * @package src/app/Http/Middleware/MerchantAuthMiddleware.php
 * @time 27.05.2026
 */
final readonly class MerchantAuthMiddleware
{
    /**
     * @param  JwtService  $jwtService
     */
    public function __construct(
        private JwtService $jwtService,
    ) {
    }

    /**
     * @param  Request  $request
     * @param  Closure  $next
     *
     * @return Response
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer === null) {
            throw new AuthenticationException('Token not provided.');
        }

        try {
            $token = $this->jwtService->decode($bearer, (string) config('jwt.secret'));
        } catch (RequiredConstraintsViolated) {
            throw new AuthenticationException('Invalid or expired token.');
        }

        $claims = $token->claims();

        $payload = new MerchantTokenPayload(
            userId:       (int) $claims->get('sub'),
            merchantId:   (int) $claims->get('merchant_id'),
            merchantUuid: (string) $claims->get('merchant_uuid', ''),
        );

        $request->attributes->set('merchant', $payload);

        return $next($request);
    }
}
