<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Xml\AcquirerStatusRequest;
use BankId\Merchant\Xml\AcquirerTrxRequest;
use BankId\Merchant\Xml\AuthnRequestBuilder;
use BankId\Merchant\Xml\DirectoryRequest;
use BankId\Merchant\Xml\Merchant;
use BankId\Merchant\Xml\XmlUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class XmlRequestBuildersTest extends TestCase
{
    private const NS = 'http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0';

    #[Test]
    public function directory_request_has_correct_structure(): void
    {
        $merchant = new Merchant('1234567890', '0');
        $request = new DirectoryRequest($merchant);
        $dom = $request->toXml();

        $this->assertSame('DirectoryReq', $dom->documentElement->localName);
        $this->assertSame(self::NS, $dom->documentElement->namespaceURI);
        $this->assertSame('NL:BVN:BankID:1.0', $dom->documentElement->getAttribute('productID'));
        $this->assertSame('1.0.0', $dom->documentElement->getAttribute('version'));

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('idx', self::NS);

        $this->assertSame(1, $xpath->query('//idx:createDateTimestamp')->length);
        $this->assertSame('1234567890', $xpath->query('//idx:Merchant/idx:merchantID')->item(0)->textContent);
        $this->assertSame('0', $xpath->query('//idx:Merchant/idx:subID')->item(0)->textContent);
    }

    #[Test]
    public function directory_request_timestamp_is_utc(): void
    {
        $merchant = new Merchant('1234567890', '0');
        $dom = (new DirectoryRequest($merchant))->toXml();

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('idx', self::NS);

        $timestamp = $xpath->query('//idx:createDateTimestamp')->item(0)->textContent;
        $this->assertStringEndsWith('Z', $timestamp);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $timestamp);
    }

    #[Test]
    public function acquirer_status_request_has_correct_structure(): void
    {
        $merchant = new Merchant('1234567890', '0');
        $request = new AcquirerStatusRequest($merchant, 'TRX-12345');
        $dom = $request->toXml();

        $this->assertSame('AcquirerStatusReq', $dom->documentElement->localName);
        $this->assertSame(self::NS, $dom->documentElement->namespaceURI);
        $this->assertSame('NL:BVN:BankID:1.0', $dom->documentElement->getAttribute('productID'));

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('idx', self::NS);

        $this->assertSame('1234567890', $xpath->query('//idx:Merchant/idx:merchantID')->item(0)->textContent);
        $this->assertSame('TRX-12345', $xpath->query('//idx:Transaction/idx:transactionID')->item(0)->textContent);
    }

    #[Test]
    public function acquirer_trx_request_has_correct_structure(): void
    {
        $merchant = new Merchant('1234567890', '42');

        $authnRequest = new AuthnRequestBuilder(
            '1234567890',
            'https://example.com/return',
            'Aref123',
            '16384',
            'nl:bvn:bankid:1.0:loa3',
            '',
        );
        $containedData = $authnRequest->toXml();

        $request = new AcquirerTrxRequest(
            $merchant,
            'https://example.com/return',
            'INGBNL2A',
            'entrance1',
            'PT2M',
            'nl',
            $containedData,
        );
        $dom = $request->toXml();

        $this->assertSame('AcquirerTrxReq', $dom->documentElement->localName);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('idx', self::NS);
        $xpath->registerNamespace('samlp', XmlUtility::NS_PROTOCOL);

        $this->assertSame('1234567890', $xpath->query('//idx:Merchant/idx:merchantID')->item(0)->textContent);
        $this->assertSame('42', $xpath->query('//idx:Merchant/idx:subID')->item(0)->textContent);
        $this->assertSame('INGBNL2A', $xpath->query('//idx:Issuer/idx:issuerID')->item(0)->textContent);
        $this->assertSame('entrance1', $xpath->query('//idx:Transaction/idx:entranceCode')->item(0)->textContent);
        $this->assertSame('PT2M', $xpath->query('//idx:Transaction/idx:expirationPeriod')->item(0)->textContent);
        $this->assertSame('nl', $xpath->query('//idx:Transaction/idx:language')->item(0)->textContent);
        $this->assertSame(1, $xpath->query('//idx:container/samlp:AuthnRequest')->length);
    }

    #[Test]
    public function acquirer_trx_request_omits_optional_fields(): void
    {
        $merchant = new Merchant('1234567890', '0');

        $authnRequest = new AuthnRequestBuilder(
            '1234567890',
            'https://example.com/return',
            'Aref123',
            '16384',
            'nl:bvn:bankid:1.0:loa3',
            '',
        );

        $request = new AcquirerTrxRequest(
            $merchant,
            'https://example.com/return',
            'INGBNL2A',
            'entrance1',
            null,
            null,
            $authnRequest->toXml(),
        );
        $dom = $request->toXml();

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('idx', self::NS);

        $this->assertSame(0, $xpath->query('//idx:Transaction/idx:expirationPeriod')->length);
        $this->assertSame(0, $xpath->query('//idx:Transaction/idx:language')->length);
    }

    #[Test]
    public function authn_request_builder_has_correct_attributes(): void
    {
        $builder = new AuthnRequestBuilder(
            'MERCHANT1',
            'https://example.com/return',
            'Aref123',
            '16384',
            'nl:bvn:bankid:1.0:loa3',
            '',
        );
        $dom = $builder->toXml();

        $root = $dom->documentElement;
        $this->assertSame('AuthnRequest', $root->localName);
        $this->assertSame(XmlUtility::NS_PROTOCOL, $root->namespaceURI);
        $this->assertSame('https://example.com/return', $root->getAttribute('AssertionConsumerServiceURL'));
        $this->assertSame('16384', $root->getAttribute('AttributeConsumingServiceIndex'));
        $this->assertSame('true', $root->getAttribute('Consent'));
        $this->assertSame('true', $root->getAttribute('ForceAuthn'));
        $this->assertSame('Aref123', $root->getAttribute('ID'));
        $this->assertSame('2.0', $root->getAttribute('Version'));
        $this->assertSame('nl:bvn:bankid:1.0:protocol:iDx', $root->getAttribute('ProtocolBinding'));
    }

    #[Test]
    public function authn_request_builder_includes_issuer(): void
    {
        $builder = new AuthnRequestBuilder(
            'MERCHANT1',
            'https://example.com/return',
            'Aref123',
            '16384',
            'nl:bvn:bankid:1.0:loa3',
            '',
        );
        $dom = $builder->toXml();

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('saml', XmlUtility::NS_ASSERTION);

        $issuer = $xpath->query('//saml:Issuer')->item(0);
        $this->assertSame('MERCHANT1', $issuer->textContent);
    }

    #[Test]
    public function authn_request_builder_includes_document_id_when_provided(): void
    {
        $builder = new AuthnRequestBuilder(
            'MERCHANT1',
            'https://example.com/return',
            'Aref123',
            '16392',
            'nl:bvn:bankid:1.0:loa3',
            'MyDocument123',
        );
        $dom = $builder->toXml();

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('saml', XmlUtility::NS_ASSERTION);
        $xpath->registerNamespace('samlp', XmlUtility::NS_PROTOCOL);

        $attrValue = $xpath->query('//saml:Attribute[@Name="urn:nl:bvn:bankid:1.0:merchant.documentID"]/saml:AttributeValue')->item(0);
        $this->assertNotNull($attrValue);
        $this->assertSame('MyDocument123', $attrValue->textContent);
    }

    #[Test]
    public function authn_request_builder_omits_extensions_when_no_document_id(): void
    {
        $builder = new AuthnRequestBuilder(
            'MERCHANT1',
            'https://example.com/return',
            'Aref123',
            '16384',
            'nl:bvn:bankid:1.0:loa3',
            '',
        );
        $dom = $builder->toXml();

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('saml', XmlUtility::NS_ASSERTION);

        $this->assertSame(0, $xpath->query('//saml:Attribute')->length);
    }

    #[Test]
    public function authn_request_builder_includes_authn_context(): void
    {
        $builder = new AuthnRequestBuilder(
            'MERCHANT1',
            'https://example.com/return',
            'Aref123',
            '16384',
            'nl:bvn:bankid:1.0:loa3',
            '',
        );
        $dom = $builder->toXml();

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('saml', XmlUtility::NS_ASSERTION);

        $ref = $xpath->query('//saml:AuthnContextClassRef')->item(0);
        $this->assertSame('nl:bvn:bankid:1.0:loa3', $ref->textContent);
    }
}
