<?php

declare(strict_types=1);

namespace BankId\Merchant\Response;

class SamlStatus
{
    public function __construct(
        public readonly string $statusMessage,
        public readonly string $statusCodeFirstLevel,
        public readonly string $statusCodeSecondLevel,
    ) {
    }
}
