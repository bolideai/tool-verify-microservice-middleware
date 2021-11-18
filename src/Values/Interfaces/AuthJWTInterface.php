<?php

declare(strict_types=1);

namespace Bolideai\VerifyMicroservice\Values\Interfaces;

use Carbon\Carbon;

interface AuthJWTInterface
{
    public function expirationDate(): Carbon;
    public function storeId(): int;
    public function storeDomain(): string;
    public function subject(): string;
    public function validateToken(): void;
}
