<?php

declare(strict_types=1);

namespace Bolideai\VerifyMicroservice\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Assert\AssertionFailedException;
use Bolideai\VerifyMicroservice\Values\AuthJWT;
use Illuminate\Http\Resources\Json\JsonResource;
use Bolideai\VerifyMicroservice\Http\Resources\ApiErrorResource;

class VerifyMicroservice
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tokenSource = $this->getAccessTokenFromRequest($request);

        if ($tokenSource === null) {
            return $this->invalidRequest(AuthJWT::EXCEPTION_INVALID);
        }

        try {
            $decodedToken = new AuthJWT($tokenSource);
            $decodedToken->validateToken();
        } catch (AssertionFailedException $e) {
            return $this->invalidRequest($e->getMessage());
        }

        $request = $this->addPropertiesToRequest($request, $decodedToken);

        return $next($request);
    }

    protected function getAccessTokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken() ?? $request->get('token');
    }

    protected function addPropertiesToRequest(Request $request, AuthJWT $decodedToken): void
    {
        $request->merge([
            'user_id' => $decodedToken->storeId(),
            'token' => $decodedToken
        ]);
    }

    protected function invalidRequest(string $message): JsonResource
    {
        return new ApiErrorResource([
            'status' => 'Failed',
            'message' => $message
        ]);
    }
}
