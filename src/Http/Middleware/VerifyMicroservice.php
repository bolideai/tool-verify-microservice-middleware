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
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
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

    /**
     * Get the token from request (if available).
     *
     * @param Request $request The request object.
     *
     * @return string
     */
    protected function getAccessTokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken() ?? $request->get('token');
    }

    /**
     * Adds data to global request from the access token
     *
     * @param Request $request
     * @param AuthJWT $decodedToken
     * @return Request
     */
    protected function addPropertiesToRequest(Request $request, AuthJWT $decodedToken): Request
    {
        $request->request->add([
            'user_id' => $decodedToken->storeId(),
            'token' => $decodedToken
        ]);

        return $request;
    }

    /**
     * Undocumented function
     *
     * @param string $message
     * @return JsonResource
     */
    protected function invalidRequest(string $message): JsonResource
    {
        return new ApiErrorResource([
            'status' => 'Failed',
            'message' => $message
        ]);
    }
}
