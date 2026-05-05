<?php

declare(strict_types=1);

namespace BankId\Merchant\Xml;

final class Merchant
{
    public function __construct(
        public readonly string $merchantID,
        public readonly string $subID,
    ) {
    }
}
