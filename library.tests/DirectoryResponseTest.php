<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Response\DirectoryResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DirectoryResponseTest extends TestCase
{
    #[Test]
    public function parses_directory_response_with_issuers(): void
    {
        $xml = <<<XML
        <DirectoryRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0" productID="NL:BVN:BankID:1.0" version="1.0.0">
            <createDateTimestamp>2015-05-29T14:12:13.264Z</createDateTimestamp>
            <Directory>
                <directoryDateTimestamp>2015-05-29T14:12:13.264Z</directoryDateTimestamp>
                <Country>
                    <countryNames>Netherlands</countryNames>
                    <Issuer>
                        <issuerID>INGBNL2A</issuerID>
                        <issuerName>ING Bank</issuerName>
                    </Issuer>
                    <Issuer>
                        <issuerID>RABONL2U</issuerID>
                        <issuerName>Rabobank</issuerName>
                    </Issuer>
                </Country>
                <Country>
                    <countryNames>Belgium</countryNames>
                    <Issuer>
                        <issuerID>KREDBEBB</issuerID>
                        <issuerName>KBC Bank</issuerName>
                    </Issuer>
                </Country>
            </Directory>
        </DirectoryRes>
        XML;

        $response = new DirectoryResponse($xml);

        $this->assertFalse($response->isError);
        $this->assertNull($response->error);
        $this->assertSame('2015-05-29T14:12:13.264Z', $response->directoryDateTimestamp);
        $this->assertCount(3, $response->issuers);

        $this->assertSame('Netherlands', $response->issuers[0]->country);
        $this->assertSame('ING Bank', $response->issuers[0]->name);
        $this->assertSame('INGBNL2A', $response->issuers[0]->id);

        $this->assertSame('Netherlands', $response->issuers[1]->country);
        $this->assertSame('Rabobank', $response->issuers[1]->name);
        $this->assertSame('RABONL2U', $response->issuers[1]->id);

        $this->assertSame('Belgium', $response->issuers[2]->country);
        $this->assertSame('KBC Bank', $response->issuers[2]->name);
        $this->assertSame('KREDBEBB', $response->issuers[2]->id);
    }

    #[Test]
    public function get_issuers_by_country_groups_correctly(): void
    {
        $xml = <<<XML
        <DirectoryRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0" productID="NL:BVN:BankID:1.0" version="1.0.0">
            <createDateTimestamp>2015-05-29T14:12:13.264Z</createDateTimestamp>
            <Directory>
                <directoryDateTimestamp>2015-05-29T14:12:13.264Z</directoryDateTimestamp>
                <Country>
                    <countryNames>Netherlands</countryNames>
                    <Issuer>
                        <issuerID>INGBNL2A</issuerID>
                        <issuerName>ING Bank</issuerName>
                    </Issuer>
                    <Issuer>
                        <issuerID>RABONL2U</issuerID>
                        <issuerName>Rabobank</issuerName>
                    </Issuer>
                </Country>
                <Country>
                    <countryNames>Belgium</countryNames>
                    <Issuer>
                        <issuerID>KREDBEBB</issuerID>
                        <issuerName>KBC Bank</issuerName>
                    </Issuer>
                </Country>
            </Directory>
        </DirectoryRes>
        XML;

        $response = new DirectoryResponse($xml);
        $grouped = $response->getIssuersByCountry();

        $this->assertCount(2, $grouped);
        $this->assertArrayHasKey('Netherlands', $grouped);
        $this->assertArrayHasKey('Belgium', $grouped);
        $this->assertCount(2, $grouped['Netherlands']);
        $this->assertCount(1, $grouped['Belgium']);
        $this->assertSame('INGBNL2A', $grouped['Netherlands'][0]->id);
        $this->assertSame('KREDBEBB', $grouped['Belgium'][0]->id);
    }

    #[Test]
    public function parses_error_response(): void
    {
        $xml = <<<XML
        <AcquirerErrorRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0">
            <Error>
                <errorCode>SE2000</errorCode>
                <errorMessage>System error</errorMessage>
                <errorDetail>Internal</errorDetail>
                <suggestedAction>Retry</suggestedAction>
                <consumerMessage>Try again</consumerMessage>
            </Error>
        </AcquirerErrorRes>
        XML;

        $response = new DirectoryResponse($xml);

        $this->assertTrue($response->isError);
        $this->assertNotNull($response->error);
        $this->assertSame('SE2000', $response->error->errorCode);
    }

    #[Test]
    public function handles_non_xml_as_error(): void
    {
        $response = new DirectoryResponse('timeout');

        $this->assertTrue($response->isError);
        $this->assertSame('timeout', $response->error->errorMessage);
    }

    #[Test]
    public function empty_directory_returns_no_issuers(): void
    {
        $xml = <<<XML
        <DirectoryRes xmlns="http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0" productID="NL:BVN:BankID:1.0" version="1.0.0">
            <createDateTimestamp>2015-05-29T14:12:13.264Z</createDateTimestamp>
            <Directory>
                <directoryDateTimestamp>2015-05-29T14:12:13.264Z</directoryDateTimestamp>
            </Directory>
        </DirectoryRes>
        XML;

        $response = new DirectoryResponse($xml);

        $this->assertFalse($response->isError);
        $this->assertCount(0, $response->issuers);
        $this->assertSame([], $response->getIssuersByCountry());
    }
}
