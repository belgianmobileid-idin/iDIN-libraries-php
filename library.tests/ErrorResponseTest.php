<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Response\ErrorResponse;
use BankId\Merchant\Response\SamlStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ErrorResponseTest extends TestCase
{
    #[Test]
    public function from_message_sets_error_message(): void
    {
        $error = ErrorResponse::fromMessage('Something went wrong');

        $this->assertSame('Something went wrong', $error->errorMessage);
        $this->assertSame('', $error->errorCode);
        $this->assertSame('', $error->errorDetails);
        $this->assertSame('', $error->suggestedAction);
        $this->assertSame('', $error->consumerMessage);
        $this->assertNull($error->additionalInformation);
    }

    #[Test]
    public function from_exception_extracts_message(): void
    {
        $exception = new \RuntimeException('Connection failed');

        $error = ErrorResponse::fromException($exception);

        $this->assertSame('Connection failed', $error->errorMessage);
        $this->assertSame('', $error->errorCode);
        $this->assertSame('', $error->errorDetails);
        $this->assertNull($error->additionalInformation);
    }

    #[Test]
    public function from_exception_extracts_previous_message(): void
    {
        $previous = new \RuntimeException('DNS lookup failed');
        $exception = new \RuntimeException('Connection failed', 0, $previous);

        $error = ErrorResponse::fromException($exception);

        $this->assertSame('Connection failed', $error->errorMessage);
        $this->assertSame('DNS lookup failed', $error->errorDetails);
    }

    #[Test]
    public function from_saml_status_creates_error_with_additional_info(): void
    {
        $status = new SamlStatus('Auth failed', 'urn:oasis:names:tc:SAML:2.0:status:Requester', 'urn:oasis:names:tc:SAML:2.0:status:RequestDenied');

        $error = ErrorResponse::fromSamlStatus($status);

        $this->assertSame('SAML specific error.', $error->errorMessage);
        $this->assertSame('', $error->errorCode);
        $this->assertNotNull($error->additionalInformation);
        $this->assertSame('Auth failed', $error->additionalInformation->statusMessage);
        $this->assertSame('urn:oasis:names:tc:SAML:2.0:status:Requester', $error->additionalInformation->statusCodeFirstLevel);
        $this->assertSame('urn:oasis:names:tc:SAML:2.0:status:RequestDenied', $error->additionalInformation->statusCodeSecondLevel);
    }

    #[Test]
    public function from_xml_parses_error_response(): void
    {
        $xml = <<<XML
        <AcquirerErrorRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0">
            <Error>
                <errorCode>AP2901</errorCode>
                <errorMessage>Invalid request</errorMessage>
                <errorDetail>Missing merchant ID</errorDetail>
                <suggestedAction>Please provide a valid merchant ID</suggestedAction>
                <consumerMessage>An error occurred</consumerMessage>
            </Error>
        </AcquirerErrorRes>
        XML;

        $simpleXml = new \SimpleXMLElement($xml);
        $error = ErrorResponse::fromXml($simpleXml);

        $this->assertSame('AP2901', $error->errorCode);
        $this->assertSame('Invalid request', $error->errorMessage);
        $this->assertSame('Missing merchant ID', $error->errorDetails);
        $this->assertSame('Please provide a valid merchant ID', $error->suggestedAction);
        $this->assertSame('An error occurred', $error->consumerMessage);
        $this->assertNull($error->additionalInformation);
    }
}
