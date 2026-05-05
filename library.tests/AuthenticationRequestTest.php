<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Request\AuthenticationRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationRequestTest extends TestCase
{
    #[Test]
    public function generate_merchant_reference_starts_with_letter(): void
    {
        $ref = AuthenticationRequest::generateMerchantReference();

        $this->assertMatchesRegularExpression('/^[a-zA-Z]/', $ref);
    }

    #[Test]
    public function generate_merchant_reference_is_35_chars(): void
    {
        $ref = AuthenticationRequest::generateMerchantReference();

        $this->assertSame(35, strlen($ref));
    }

    #[Test]
    public function generate_merchant_reference_matches_format(): void
    {
        $ref = AuthenticationRequest::generateMerchantReference();

        $this->assertMatchesRegularExpression('/^[a-zA-Z][a-zA-Z0-9]{0,34}$/', $ref);
    }

    #[Test]
    public function generate_merchant_reference_is_unique(): void
    {
        $references = [];
        for ($i = 0; $i < 100; $i++) {
            $references[] = AuthenticationRequest::generateMerchantReference();
        }

        $this->assertSame(100, count(array_unique($references)));
    }

    #[Test]
    public function default_properties_are_empty(): void
    {
        $request = new AuthenticationRequest();

        $this->assertSame('', $request->entranceCode);
        $this->assertSame('', $request->language);
        $this->assertNull($request->expirationPeriod);
        $this->assertSame('', $request->issuerID);
        $this->assertSame('', $request->merchantReference);
        $this->assertSame('', $request->requestedServiceID);
        $this->assertSame('', $request->assuranceLevel);
        $this->assertSame('', $request->documentID);
    }
}
