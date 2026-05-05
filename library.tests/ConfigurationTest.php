<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Configuration\Configuration;
use BankId\Merchant\Exception\CommunicatorException;
use PHPUnit\Framework\Attributes\Test;

class ConfigurationTest extends BaseTest
{
    #[Test]
    public function constructor_sets_all_fields(): void
    {
        $this->setupConfiguration();

        $this->assertSame('1234567890', $this->config->merchantID);
        $this->assertSame('0', $this->config->merchantSubID);
        $this->assertSame('http://localhost', $this->config->merchantReturnUrl);
        $this->assertSame('http://example.com/directory', $this->config->acquirerDirectoryUrl);
        $this->assertSame('http://example.com/transaction', $this->config->acquirerTransactionUrl);
        $this->assertSame('http://example.com/status', $this->config->acquirerStatusUrl);
        $this->assertFalse($this->config->logsEnabled);
        $this->assertFalse($this->config->serviceLogsEnabled);
    }

    #[Test]
    public function loads_certificates_from_files(): void
    {
        $this->setupConfiguration();

        $this->assertNotEmpty($this->config->merchantCertificate);
        $this->assertIsArray($this->config->merchantCertificate);
        $this->assertArrayHasKey('pkey', $this->config->merchantCertificate);
        $this->assertArrayHasKey('cert', $this->config->merchantCertificate);
    }

    #[Test]
    public function loads_routing_service_certificate(): void
    {
        $this->setupConfiguration();

        $this->assertNotEmpty($this->config->routingServiceCertificate);
        $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $this->config->routingServiceCertificate);
        $this->assertStringContainsString('-----END CERTIFICATE-----', $this->config->routingServiceCertificate);
    }

    #[Test]
    public function ensure_is_valid_passes_with_complete_config(): void
    {
        $this->setupConfiguration();
        $this->expectNotToPerformAssertions();

        $this->config->ensureIsValid();
    }

    #[Test]
    public function ensure_is_valid_throws_for_missing_merchant_id(): void
    {
        $this->setupConfiguration();
        $this->config->merchantID = '';

        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('merchantID');

        $this->config->ensureIsValid();
    }

    #[Test]
    public function ensure_is_valid_throws_for_missing_return_url(): void
    {
        $this->setupConfiguration();
        $this->config->merchantReturnUrl = '';

        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('merchantReturnUrl');

        $this->config->ensureIsValid();
    }

    #[Test]
    public function ensure_is_valid_throws_for_missing_directory_url(): void
    {
        $this->setupConfiguration();
        $this->config->acquirerDirectoryUrl = '';

        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('acquirerDirectoryUrl');

        $this->config->ensureIsValid();
    }

    #[Test]
    public function ensure_is_valid_throws_for_missing_transaction_url(): void
    {
        $this->setupConfiguration();
        $this->config->acquirerTransactionUrl = '';

        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('acquirerTransactionUrl');

        $this->config->ensureIsValid();
    }

    #[Test]
    public function ensure_is_valid_throws_for_missing_status_url(): void
    {
        $this->setupConfiguration();
        $this->config->acquirerStatusUrl = '';

        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('acquirerStatusUrl');

        $this->config->ensureIsValid();
    }

    #[Test]
    public function normalize_certificate_reformats_correctly(): void
    {
        $raw = "-----BEGIN CERTIFICATE-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA\n-----END CERTIFICATE-----";

        $normalized = Configuration::normalizeCertificate($raw);

        $this->assertStringStartsWith('-----BEGIN CERTIFICATE-----', $normalized);
        $this->assertStringEndsWith("-----END CERTIFICATE-----\n", $normalized);
        $lines = explode("\n", trim($normalized));
        $this->assertSame('-----BEGIN CERTIFICATE-----', $lines[0]);
        $this->assertSame('-----END CERTIFICATE-----', end($lines));
    }

    #[Test]
    public function normalize_certificate_handles_whitespace_and_newlines(): void
    {
        $messy = "-----BEGIN CERTIFICATE-----\r\n  ABCDEF  \r\nGHIJKL\n-----END CERTIFICATE-----";

        $normalized = Configuration::normalizeCertificate($messy);

        $this->assertStringNotContainsString("\r", $normalized);
        $this->assertStringNotContainsString(' ', substr(
            $normalized,
            strlen("-----BEGIN CERTIFICATE-----\n"),
            -strlen("-----END CERTIFICATE-----\n")
        ));
    }

    #[Test]
    public function get_version_returns_string(): void
    {
        $this->setupConfiguration();

        $this->assertSame('1.3.0', $this->config->getVersion());
    }

    #[Test]
    public function load_parses_xml_config_file(): void
    {
        $configXml = tempnam(sys_get_temp_dir(), 'idin_test_');
        file_put_contents($configXml, <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <configuration>
            <appSettings>
                <add key="BankId.Merchant.MerchantID" value="9999999999" />
                <add key="BankId.Merchant.SubID" value="5" />
                <add key="BankId.Merchant.ReturnUrl" value="https://example.com/return" />
                <add key="BankId.Acquirer.DirectoryUrl" value="https://example.com/dir" />
                <add key="BankId.Acquirer.TransactionUrl" value="https://example.com/trx" />
                <add key="BankId.Acquirer.StatusUrl" value="https://example.com/status" />
                <add key="BankId.Merchant.Certificate.File" value="" />
                <add key="BankId.Merchant.Certificate.Password" value="" />
                <add key="BankId.RoutingService.Certificate.File" value="" />
                <add key="BankId.SAML.Certificate.File" value="" />
                <add key="BankId.SAML.Certificate.Password" value="" />
            </appSettings>
        </configuration>
        XML);

        try {
            $config = Configuration::load($configXml);

            $this->assertSame('9999999999', $config->merchantID);
            $this->assertSame('5', $config->merchantSubID);
            $this->assertSame('https://example.com/return', $config->merchantReturnUrl);
            $this->assertSame('https://example.com/dir', $config->acquirerDirectoryUrl);
            $this->assertSame('https://example.com/trx', $config->acquirerTransactionUrl);
            $this->assertSame('https://example.com/status', $config->acquirerStatusUrl);
        } finally {
            unlink($configXml);
        }
    }
}
