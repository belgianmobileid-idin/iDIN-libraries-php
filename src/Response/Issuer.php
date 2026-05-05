<?php

declare(strict_types=1);

namespace BankId\Merchant\Response;

final class Issuer
{
    public function __construct(
        public readonly string $country,
        public readonly string $name,
        public readonly string $id,
    ) {
    }
}
