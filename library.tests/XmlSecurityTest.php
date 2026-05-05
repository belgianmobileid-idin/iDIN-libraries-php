<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Exception\CommunicatorException;
use BankId\Merchant\Logger\NullLogger;
use BankId\Merchant\Xml\DirectoryRequest;
use BankId\Merchant\Xml\Merchant;
use BankId\Merchant\Xml\XmlSecurity;
use PHPUnit\Framework\Attributes\Test;

class XmlSecurityTest extends BaseTest
{
    private static function generateSelfSignedCert(): string
    {
        $configFile = tempnam(sys_get_temp_dir(), 'ossl_csr_');
        file_put_contents($configFile, "[req]\ndefault_bits = 2048\ndistinguished_name = dn\n[dn]\n");

        $privKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $configFile]);
        $csr = openssl_csr_new(['commonName' => 'Test'], $privKey, ['config' => $configFile, 'digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $privKey, 1, ['config' => $configFile, 'digest_alg' => 'sha256']);
        openssl_x509_export($x509, $cert);

        unlink($configFile);
        return $cert;
    }

    #[Test]
    public function sign_and_verify_roundtrip(): void
    {
        $this->setupConfiguration();

        $signer = new XmlSecurity(new NullLogger());

        $directoryReq = new DirectoryRequest(new Merchant('1234567890', '0'));
        $xml = $directoryReq->toXml()->saveXML();

        $signed = $signer->addSignature($this->config, $xml);

        $this->assertStringContainsString('<Signature', $signed);
        $this->assertStringContainsString('<KeyName>', $signed);
        $this->assertStringContainsString('<SignatureValue>', $signed);

        // Verify with the same certificate as routing service cert
        $this->config->routingServiceCertificate = $this->config->merchantCertificate['cert'];
        $signer->verifySignature($this->config, $signed);

        // If we get here without exception, verification passed
        $this->assertTrue(true);
    }

    #[Test]
    public function verify_fails_with_wrong_certificate(): void
    {
        $this->setupConfiguration();

        $signer = new XmlSecurity(new NullLogger());

        $directoryReq = new DirectoryRequest(new Merchant('1234567890', '0'));
        $xml = $directoryReq->toXml()->saveXML();
        $signed = $signer->addSignature($this->config, $xml);

        $this->config->routingServiceCertificate = self::generateSelfSignedCert();
        $this->config->routingServiceCertificateAlternative = '';

        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('The signature could not be verified');
        $signer->verifySignature($this->config, $signed);
    }

    #[Test]
    public function verify_fails_when_no_signature_present(): void
    {
        $this->setupConfiguration();

        $signer = new XmlSecurity(new NullLogger());
        $xml = '<root><child>test</child></root>';

        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('Cannot locate Signature Node');
        $signer->verifySignature($this->config, $xml);
    }

    #[Test]
    public function add_signature_embeds_fingerprint(): void
    {
        $this->setupConfiguration();

        $signer = new XmlSecurity(new NullLogger());

        $directoryReq = new DirectoryRequest(new Merchant('1234567890', '0'));
        $xml = $directoryReq->toXml()->saveXML();

        $signed = $signer->addSignature($this->config, $xml);

        $doc = new \DOMDocument();
        $doc->loadXML($signed);
        $keyNames = $doc->getElementsByTagName('KeyName');

        $this->assertSame(1, $keyNames->length);
        $fingerprint = $keyNames->item(0)->textContent;
        $this->assertMatchesRegularExpression('/^[A-F0-9]{40}$/', $fingerprint);
    }

    #[Test]
    public function verify_accepts_alternative_certificate(): void
    {
        $this->setupConfiguration();

        $signer = new XmlSecurity(new NullLogger());

        $directoryReq = new DirectoryRequest(new Merchant('1234567890', '0'));
        $xml = $directoryReq->toXml()->saveXML();
        $signed = $signer->addSignature($this->config, $xml);

        $correctCert = $this->config->merchantCertificate['cert'];

        $this->config->routingServiceCertificate = self::generateSelfSignedCert();
        $this->config->routingServiceCertificateAlternative = $correctCert;

        $signer->verifySignature($this->config, $signed);
        $this->assertTrue(true);
    }

    #[Test]
    public function signed_xml_contains_enveloped_signature(): void
    {
        $this->setupConfiguration();

        $signer = new XmlSecurity(new NullLogger());

        $directoryReq = new DirectoryRequest(new Merchant('1234567890', '0'));
        $xml = $directoryReq->toXml()->saveXML();
        $signed = $signer->addSignature($this->config, $xml);

        $this->assertStringContainsString('http://www.w3.org/2000/09/xmldsig#enveloped-signature', $signed);
        $this->assertStringContainsString('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256', $signed);
    }
}
