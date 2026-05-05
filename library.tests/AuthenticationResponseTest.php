<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Response\AuthenticationResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationResponseTest extends TestCase
{
    #[Test]
    public function parses_successful_transaction_response(): void
    {
        $xml = file_get_contents(__DIR__ . '/messages/TransactionResponse-OK-1.xml');
        $response = new AuthenticationResponse($xml);

        $this->assertFalse($response->isError);
        $this->assertNull($response->error);
        $this->assertSame('1234000000002144', $response->transactionID);
        $this->assertSame('2015-05-29T14:12:12.779Z', $response->transactionCreateDateTimestamp);
        $this->assertSame('http://example.com', $response->issuerAuthenticationURL);
        $this->assertSame($xml, $response->rawMessage);
    }

    #[Test]
    public function parses_error_response_xml(): void
    {
        $xml = <<<XML
        <AcquirerErrorRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0">
            <Error>
                <errorCode>AP2901</errorCode>
                <errorMessage>Invalid request</errorMessage>
                <errorDetail>Bad data</errorDetail>
                <suggestedAction>Fix it</suggestedAction>
                <consumerMessage>Error</consumerMessage>
            </Error>
        </AcquirerErrorRes>
        XML;

        $response = new AuthenticationResponse($xml);

        $this->assertTrue($response->isError);
        $this->assertNotNull($response->error);
        $this->assertSame('AP2901', $response->error->errorCode);
        $this->assertSame('Invalid request', $response->error->errorMessage);
        $this->assertNull($response->transactionID);
        $this->assertNull($response->issuerAuthenticationURL);
    }

    #[Test]
    public function handles_non_xml_response_as_error(): void
    {
        $response = new AuthenticationResponse('Connection refused');

        $this->assertTrue($response->isError);
        $this->assertNotNull($response->error);
        $this->assertSame('Connection refused', $response->error->errorMessage);
    }

    #[Test]
    public function stores_raw_message(): void
    {
        $raw = 'some raw content';
        $response = new AuthenticationResponse($raw);

        $this->assertSame($raw, $response->rawMessage);
    }
}
