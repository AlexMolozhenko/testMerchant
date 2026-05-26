<?php

declare(strict_types=1);

namespace App\Shared\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class AbstractApiController
 *
 * @author Molozhenko
 * @package src/app/Shared/Controllers/AbstractApiController.php
 * @time 26.05.2026
 */
abstract class AbstractApiController extends Controller
{
    /**
     * @param  array  $data
     *
     * @return JsonResponse
     */
    protected function success(array $data): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }

    /**
     * @param  string  $message
     *
     * @return JsonResponse
     */
    protected function create(string $message = 'Created successfully.'): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
        ], 201);
    }

    /**
     * @param  string|null  $message
     *
     * @return JsonResponse
     */
    protected function noContent(?string $message = null): JsonResponse
    {
        if ($message !== null) {
            return response()->json([
                'status'  => true,
                'message' => $message,
            ], 204);
        }

        return response()->json(null, 204);
    }

    /**
     * @param  string  $message
     * @param  array|null  $errors
     * @param  int  $status
     *
     * @return JsonResponse
     */
    protected function error(string $message, ?array $errors = null, int $status = 422): JsonResponse
    {
        $body = [
            'status'  => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
