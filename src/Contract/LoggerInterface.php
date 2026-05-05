<?php

declare(strict_types=1);

namespace BankId\Merchant\Contract;

interface LoggerInterface
{
    public function log(string $message): void;

    public function logXmlMessage(\DOMDocument|string $dom, bool $isXML = false, string $fileName = ''): void;
}
