<?php

declare(strict_types=1);

namespace BankId\Merchant\Logger;

use BankId\Merchant\Contract\LoggerInterface;

final class NullLogger implements LoggerInterface
{
    public function log(string $message): void
    {
    }

    public function logXmlMessage(\DOMDocument|string $dom, bool $isXML = false, string $fileName = ''): void
    {
    }
}
