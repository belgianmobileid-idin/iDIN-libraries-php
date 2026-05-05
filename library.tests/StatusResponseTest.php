<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Enum\TransactionStatus;
use BankId\Merchant\Response\StatusResponse;
use PHPUnit\Framework\Attributes\Test;

class StatusResponseTest extends BaseTest
{
    #[Test]
    public function parses_non_xml_as_error(): void
    {
        $this->setupConfiguration();
        $response = new StatusResponse($this->config, 'Connection timeout');

        $this->assertTrue($response->isError);
        $this->assertNotNull($response->error);
        $this->assertSame('Connection timeout', $response->error->errorMessage);
        $this->assertSame('Connection timeout', $response->rawMessage);
    }

    #[Test]
    public function parses_acquirer_error_response(): void
    {
        $this->setupConfiguration();

        $xml = <<<XML
        <AcquirerErrorRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0">
            <Error>
                <errorCode>SE2000</errorCode>
                <errorMessage>System error</errorMessage>
                <errorDetail>Internal failure</errorDetail>
                <suggestedAction>Retry later</suggestedAction>
                <consumerMessage>An error occurred</consumerMessage>
            </Error>
        </AcquirerErrorRes>
        XML;

        $response = new StatusResponse($this->config, $xml);

        $this->assertTrue($response->isError);
        $this->assertNotNull($response->error);
        $this->assertSame('SE2000', $response->error->errorCode);
        $this->assertSame('System error', $response->error->errorMessage);
    }

    #[Test]
    public function parses_open_status(): void
    {
        $this->setupConfiguration();

        $xml = <<<XML
        <AcquirerStatusRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0" version="1.0.0" productID="NL:BVN:BankID:1.0">
            <createDateTimestamp>2015-07-15T10:10:10.123Z</createDateTimestamp>
            <Acquirer>
                <acquirerID>4444</acquirerID>
            </Acquirer>
            <Transaction>
                <transactionID>1234567890123456</transactionID>
                <status>Open</status>
            </Transaction>
        </AcquirerStatusRes>
        XML;

        $response = new StatusResponse($this->config, $xml);

        $this->assertFalse($response->isError);
        $this->assertSame('1234567890123456', $response->transactionID);
        $this->assertSame(TransactionStatus::Open, $response->status);
        $this->assertNull($response->samlResponse);
    }

    #[Test]
    public function parses_pending_status(): void
    {
        $this->setupConfiguration();

        $xml = <<<XML
        <AcquirerStatusRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0" version="1.0.0" productID="NL:BVN:BankID:1.0">
            <createDateTimestamp>2015-07-15T10:10:10.123Z</createDateTimestamp>
            <Acquirer><acquirerID>4444</acquirerID></Acquirer>
            <Transaction>
                <transactionID>1234567890123456</transactionID>
                <status>Pending</status>
            </Transaction>
        </AcquirerStatusRes>
        XML;

        $response = new StatusResponse($this->config, $xml);

        $this->assertFalse($response->isError);
        $this->assertSame(TransactionStatus::Pending, $response->status);
    }

    #[Test]
    public function parses_cancelled_status(): void
    {
        $this->setupConfiguration();

        $xml = <<<XML
        <AcquirerStatusRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0" version="1.0.0" productID="NL:BVN:BankID:1.0">
            <createDateTimestamp>2015-07-15T10:10:10.123Z</createDateTimestamp>
            <Acquirer><acquirerID>4444</acquirerID></Acquirer>
            <Transaction>
                <transactionID>1234567890123456</transactionID>
                <status>Cancelled</status>
            </Transaction>
        </AcquirerStatusRes>
        XML;

        $response = new StatusResponse($this->config, $xml);

        $this->assertFalse($response->isError);
        $this->assertSame(TransactionStatus::Cancelled, $response->status);
    }

    #[Test]
    public function parses_expired_status(): void
    {
        $this->setupConfiguration();

        $xml = <<<XML
        <AcquirerStatusRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0" version="1.0.0" productID="NL:BVN:BankID:1.0">
            <createDateTimestamp>2015-07-15T10:10:10.123Z</createDateTimestamp>
            <Acquirer><acquirerID>4444</acquirerID></Acquirer>
            <Transaction>
                <transactionID>1234567890123456</transactionID>
                <status>Expired</status>
            </Transaction>
        </AcquirerStatusRes>
        XML;

        $response = new StatusResponse($this->config, $xml);

        $this->assertFalse($response->isError);
        $this->assertSame(TransactionStatus::Expired, $response->status);
    }

    #[Test]
    public function parses_failure_status(): void
    {
        $this->setupConfiguration();

        $xml = <<<XML
        <AcquirerStatusRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0" version="1.0.0" productID="NL:BVN:BankID:1.0">
            <createDateTimestamp>2015-07-15T10:10:10.123Z</createDateTimestamp>
            <Acquirer><acquirerID>4444</acquirerID></Acquirer>
            <Transaction>
                <transactionID>1234567890123456</transactionID>
                <status>Failure</status>
            </Transaction>
        </AcquirerStatusRes>
        XML;

        $response = new StatusResponse($this->config, $xml);

        $this->assertFalse($response->isError);
        $this->assertSame(TransactionStatus::Failure, $response->status);
    }

    #[Test]
    public function parses_success_status_with_saml_response(): void
    {
        $this->setupConfiguration();

        $xml = file_get_contents(__DIR__ . '/messages/StatusResponse-Sample-015.xml');
        $response = new StatusResponse($this->config, $xml);

        $this->assertFalse($response->isError);
        $this->assertSame('1234567890123457', $response->transactionID);
        $this->assertSame(TransactionStatus::Success, $response->status);
        $this->assertNotNull($response->samlResponse);
        $this->assertNotNull($response->statusDateTimestamp);
    }
}
