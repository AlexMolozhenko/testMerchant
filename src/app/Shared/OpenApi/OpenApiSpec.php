<?php

declare(strict_types=1);

namespace App\Shared\OpenApi;

/**
 * @OA\Info(
 *     title="UEX Merchant Platform API",
 *     version="1.0.0",
 *     description="REST API for UEX merchant cabinet"
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Current environment"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Response(
 *     response="Unauthorized",
 *     description="Unauthenticated or invalid token",
 *     @OA\JsonContent(
 *         @OA\Property(property="status", type="boolean", example=false),
 *         @OA\Property(property="message", type="string", example="Unauthenticated.")
 *     )
 * )
 *
 * @OA\Response(
 *     response="ValidationError",
 *     description="Validation failed",
 *     @OA\JsonContent(
 *         @OA\Property(property="status", type="boolean", example=false),
 *         @OA\Property(property="message", type="string", example="Validation failed."),
 *         @OA\Property(
 *             property="errors",
 *             type="object",
 *             example={"field": {"The field is required."}}
 *         )
 *     )
 * )
 */
final class OpenApiSpec
{
}
