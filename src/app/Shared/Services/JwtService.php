<?php

declare(strict_types=1);

namespace App\Shared\Services;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Hmac\Sha384;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

/**
 * Class JwtService
 *
 * @author Molozhenko
 * @package src/app/Shared/Services/JwtService.php
 * @time 27.05.2026
 */
final readonly class JwtService
{
    /**
     * @param  string  $tokenString
     * @param  string  $secret
     * @param  string  $algo
     *
     * @return Plain
     */
    public function decode(string $tokenString, string $secret, string $algo = 'HS256'): Plain
    {
        $config = $this->makeConfig($secret, $algo);

        /** @var Plain $token */
        $token = $config->parser()->parse($tokenString);

        $config->validator()->assert($token, new SignedWith($config->signer(), $config->signingKey()));

        return $token;
    }

    /**
     * @param  array  $claims
     * @param  string  $secret
     * @param  int  $ttlMinutes
     * @param  string  $algo
     *
     * @return string
     * @throws \DateMalformedStringException
     */
    public function encode(array $claims, string $secret, int $ttlMinutes, string $algo = 'HS256'): string
    {
        $config = $this->makeConfig($secret, $algo);
        $now    = new DateTimeImmutable();

        $builder = $config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify("+{$ttlMinutes} minutes"));

        foreach ($claims as $name => $value) {
            if ($name === 'sub') {
                $builder = $builder->relatedTo((string) $value);
                continue;
            }
            $builder = $builder->withClaim($name, $value);
        }

        return $builder->getToken($config->signer(), $config->signingKey())->toString();
    }

    /**
     * @param  string  $secret
     * @param  string  $algo
     *
     * @return Configuration
     */
    private function makeConfig(string $secret, string $algo): Configuration
    {
        return Configuration::forSymmetricSigner(
            $this->resolveSigner($algo),
            InMemory::plainText($secret),
        );
    }

    /**
     * @param  string  $algo
     *
     * @return Signer
     */
    private function resolveSigner(string $algo): Signer
    {
        return match ($algo) {
            'HS384' => new Sha384(),
            'HS512' => new Sha512(),
            default => new Sha256(),
        };
    }
}
