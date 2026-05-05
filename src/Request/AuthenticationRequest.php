<?php

declare(strict_types=1);

namespace BankId\Merchant\Request;

class AuthenticationRequest
{
    public string $entranceCode = '';
    public string $language = '';
    public ?string $expirationPeriod = null;
    public string $issuerID = '';
    public string $merchantReference = '';
    public string $requestedServiceID = '';
    public string $assuranceLevel = '';
    public string $documentID = '';

    public static function generateMerchantReference(): string
    {
        $guid = bin2hex(random_bytes(16));
        $guid2 = bin2hex(random_bytes(16));
        return substr('A' . $guid . $guid2, 0, 35);
    }
}
