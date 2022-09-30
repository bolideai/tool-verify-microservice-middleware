<?php

declare(strict_types=1);

namespace Bolideai\VerifyMicroservice\Values;

use Assert\Assert;
use Assert\Assertion;
use Illuminate\Support\Carbon;
use Assert\AssertionFailedException;
use Bolideai\VerifyMicroservice\Util;
use Bolideai\VerifyMicroservice\Values\Interfaces\AuthJWTInterface;

final class AuthJWT implements AuthJWTInterface, \Stringable
{
    /**
     * The regex for the format of the JWT.
     *
     * @var string
     */
    public const TOKEN_FORMAT = '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/';

    /**
     * Message for malformed token.
     *
     * @var string
     */
    public const EXCEPTION_MALFORMED = 'Session token is malformed.';

    /**
     * Message for invalid token.
     *
     * @var string
     */
    public const EXCEPTION_INVALID = 'Session token is invalid.';

    /**
     * Message for expired token.
     *
     * @var string
     */
    public const EXCEPTION_EXPIRED = 'Session token has expired.';

    /**
     * Token parts.
     *
     * @var array
     */
    protected $parts;

    /**
     * Issuer.
     *
     * @var string
     */
    protected $iss;

    /**
     * Audience.
     *
     * @var string
     */
    protected $aud;

    /**
     * Subject.
     *
     * @var string
     */
    protected $sub;

    /**
     * Expiration.
     *
     * @var Carbon
     */
    protected $exp;

    /**
     * Not before.
     *
     * @var Carbon
     */
    protected $nbf;

    /**
     * Issued at.
     *
     * @var Carbon
     */
    protected $iat;

    /**
     * JWT identity.
     *
     * @var string
     */
    protected $jti;

    /**
     * Store id.
     *
     * @var int
     */
    protected $uid;

    /**
     * Store access token.
     *
     * @var string
     */
    protected $act;

    /**
     * Constructor.
     *
     * @param string $token The JWT.
     * @param bool $verifyToken Should the token be verified
     *
     * @throws AssertionFailedException
     */
    public function __construct(string $token)
    {
        $this->string = $token;
        $this->decodeToken();
    }

    /**
     * Decode and validate the formatting of the token.
     *
     * @throws AssertionFailedException If token is malformed.
     *
     * @return void
     */
    protected function decodeToken(): void
    {
        Assert::that($this->string)->regex(self::TOKEN_FORMAT, self::EXCEPTION_MALFORMED);

        $this->parts = explode('.', $this->string);
        $body = json_decode(Util::base64UrlDecode($this->parts[1]), true);

        Assert::thatAll([
            $body['iss'],
            $body['aud'],
            $body['sub'],
            $body['exp'],
            $body['nbf'],
            $body['iat'],
            $body['jti'],
            $body['uid'],
            $body['act']
        ])->notNull(self::EXCEPTION_MALFORMED);

        $this->iss = $body['iss'];
        $this->aud = $body['aud'];
        $this->sub = $body['sub'];
        $this->jti = $body['jti'];
        $this->uid = $body['uid'];
        $this->act = $body['act'];
        $this->exp = new Carbon($body['exp']);
        $this->nbf = new Carbon($body['nbf']);
        $this->iat = new Carbon($body['iat']);
    }

    /**
     * Get the expiration time of the token.
     *
     * @return Carbon
     */
    public function expirationDate(): Carbon
    {
        return $this->exp;
    }

    /**
     * Get store id from the token.
     *
     * @return int
     */
    public function storeId(): int
    {
        return $this->uid;
    }

    /**
     * Get store access token.
     *
     * @return string
     */
    public function accessToken(): string
    {
        return $this->act;
    }

    /**
     * Get store domain from the token.
     *
     * @return string
     */
    public function storeDomain(): string
    {
        return $this->iss;
    }

    /**
     * Get class subject the token.
     *
     * @return string
     */
    public function subject(): string
    {
        return $this->sub;
    }


    /**
     * Checks the validity of the signature sent with the token.
     *
     * @throws AssertionFailedException If signature does not match.
     *
     * @return void
     */
    protected function verifySignature(): void
    {
        $tokenParts = $this->parts;
        $signatureProvided = array_pop($tokenParts);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);

        $base64UrlHeader = Util::base64UrlEncode($header);
        $base64UrlPayload = Util::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->combinedKey(), true);
        $base64UrlSignature = Util::base64UrlEncode($signature);

        Assertion::same($signatureProvided, $base64UrlSignature, self::EXCEPTION_INVALID);
    }

    /**
     * Checks the token to ensure the issuer and audience matches.
     *
     * @throws AssertionFailedException If invalid token.
     *
     * @return void
     */
    protected function verifyValidity(): void
    {
        Assert::that($this->uid)->notEmpty($this->uid, self::EXCEPTION_INVALID);
        Assert::that($this->iss)->contains($this->iss, '.myshopify.com', self::EXCEPTION_INVALID);
        Assert::that($this->jti)->startsWith($this->jti, 'bolideai', self::EXCEPTION_INVALID);
        Assert::that($this->aud)->eq(config('microservice.shopify_app_key'), self::EXCEPTION_INVALID);
    }

    /**
     * Checks the token to ensure its not expired.
     *
     * @throws AssertionFailedException If token is expired.
     *
     * @return void
     */
    protected function verifyExpiration(): void
    {
        $now = Carbon::now();

        Assert::thatAll([
            $now->greaterThan($this->exp),
            $now->lessThan($this->nbf),
            $now->lessThan($this->iat),
        ])->false(self::EXCEPTION_EXPIRED);
    }

    /**
     * Get combined key
     *
     * @return string
     */
    private function combinedKey(): string
    {
        return sodium_base642bin(config('microservice.main_app_key') . config('microservice.shopify_app_key'), 1);
    }

    /**
     * Checking that the token is up to date and correct
     *
     * @return void
     */
    public function validateToken(): void
    {
        $this->verifySignature();
        $this->verifyValidity();
        $this->verifyExpiration();
    }

    public function __toString()
    {
        return $this->string;
    }
}
