<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Configuration\Configuration;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    protected Configuration $config;

    protected function setupConfiguration(): void
    {
        $certs = [];
        $pkcs12 = file_get_contents(__DIR__ . '/certificates/BankID2020.Libs.sha256.2048.csp.p12');
        if (!openssl_pkcs12_read($pkcs12, $certs, '123456')) {
            $this->markTestSkipped('Cannot read test PKCS12 certificate (OpenSSL 3.x legacy algorithm not supported)');
        }

        $this->config = new Configuration(
            merchantID: '1234567890',
            merchantSubID: '0',
            merchantReturnUrl: 'http://localhost',
            acquirerDirectoryUrl: 'http://example.com/directory',
            acquirerTransactionUrl: 'http://example.com/transaction',
            acquirerStatusUrl: 'http://example.com/status',
            merchantCertificateFile: __DIR__ . '/certificates/BankID2020.Libs.sha256.2048.csp.p12',
            merchantCertificatePassword: '123456',
            routingServiceCertificateFile: __DIR__ . '/certificates/BankID2020.QA.sha256.2048.cer',
            routingServiceCertificateFileAlternative: '',
            samlCertificateFile: __DIR__ . '/certificates/BankID2020.Libs.sha256.2048.csp.p12',
            samlCertificatePassword: '123456',
            logsEnabled: false,
            logsLocation: '',
            logsPattern: '',
            logsFileName: '',
            serviceLogsEnabled: false,
            serviceLogsLocation: '',
            serviceLogsPattern: '',
            merchantCertificate: $certs,
            samlCertificate: $certs,
        );
    }

    protected function assertMatches(string $value, string $regexp): void
    {
        $this->assertEquals(1, preg_match($regexp, $value), $value . ' does not match ' . $regexp);
    }
}
