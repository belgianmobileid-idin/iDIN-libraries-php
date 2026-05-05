<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Response\StatusResponse;
use BankId\Merchant\Saml\SamlAttribute;
use PHPUnit\Framework\Attributes\Test;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class AttributeEncryptionKeysTest extends BaseTest
{
    #[Test]
    public function no_error_in_sample_message(): void
    {
        $res = $this->invoke('StatusResponse-Sample-015');

        $this->assertFalse($res->isError);
        $this->assertNotNull($res->samlResponse);

        $this->assertEquals(
            'urn:oasis:names:tc:SAML:2.0:status:Success',
            $res->samlResponse->status->statusCodeFirstLevel
        );
    }

    #[Test]
    public function parsing_the_attributes(): void
    {
        $res = $this->invoke('StatusResponse-Sample-015');

        $attrs = $res->samlResponse->attributes;
        $this->assertCount(9, $attrs);

        $this->assertEquals('Some Subject', $attrs[SamlAttribute::ConsumerBin]);
        $this->assertEquals('Smith', $attrs[SamlAttribute::ConsumerLegalLastName]);
        $this->assertEquals('John', $attrs[SamlAttribute::ConsumerPrefLastName]);
        $this->assertEquals('Jane', $attrs[SamlAttribute::ConsumerPartnerLastName]);
        $this->assertEquals('Sm', $attrs[SamlAttribute::ConsumerLegalLastNamePrefix]);
        $this->assertEquals('Jo', $attrs[SamlAttribute::ConsumerPrefLastNamePrefix]);
        $this->assertEquals('Ja', $attrs[SamlAttribute::ConsumerPartnerLastNamePrefix]);
        $this->assertEquals('SJĆ', $attrs[SamlAttribute::ConsumerInitials]);
        $this->assertEquals('4096', $attrs[SamlAttribute::DeliveredServiceID]);
    }

    #[Test]
    public function retrieving_attributes_encryption_keys(): void
    {
        $res = $this->invoke('StatusResponse-Sample-015');

        $keys = $res->samlResponse->attributesEncryptionKeys;
        $this->assertNotNull($keys);
    }

    #[Test]
    public function recomputing_subject_based_on_encryption_keys(): void
    {
        $res = $this->invoke('StatusResponse-Sample-015');

        $attrs = $res->samlResponse->attributes;
        $this->assertEquals('Some Subject', $attrs[SamlAttribute::ConsumerBin]);

        $keys = $res->samlResponse->attributesEncryptionKeys;

        $key = $keys[0]['AesKey'];
        $attr = $keys[0]['AttributeName'];

        $value = $this->decryptWithEncryptionKey($key, $res->rawMessage, 0);

        $this->assertEquals('Some Subject', $value);
        $this->assertEquals(SamlAttribute::ConsumerBin, $attr);
    }

    #[Test]
    public function recomputing_one_consumer_attribute_value(): void
    {
        $res = $this->invoke('StatusResponse-Sample-015');

        $attrs = $res->samlResponse->attributes;
        $this->assertEquals('Smith', $attrs[SamlAttribute::ConsumerLegalLastName]);

        $keys = $res->samlResponse->attributesEncryptionKeys;

        $key = $keys[1]['AesKey'];
        $attr = $keys[1]['AttributeName'];

        $value = $this->decryptWithEncryptionKey($key, $res->rawMessage, 1);

        $this->assertEquals('Smith', $value);
        $this->assertEquals(SamlAttribute::ConsumerLegalLastName, $attr);
    }

    #[Test]
    public function recompute_all_attribute_values(): void
    {
        $res = $this->invoke('StatusResponse-Sample-015');

        $keys = $res->samlResponse->attributesEncryptionKeys;
        $this->assertCount(8, $keys);

        for ($index = 0; $index < 8; $index++) {
            $key = $keys[$index]['AesKey'];
            $attr = $keys[$index]['AttributeName'];

            $originalValue = $res->samlResponse->attributes[$attr];
            $value = $this->decryptWithEncryptionKey($key, $res->rawMessage, $index);

            $this->assertEquals($originalValue, $value);
        }
    }

    private function decryptWithEncryptionKey(string $key, string $rawXml, int $index): string
    {
        $el = $this->decryptElement($key, $rawXml, $index);
        $element = new \SimpleXMLElement($el);
        return trim(dom_import_simplexml($element)->textContent);
    }

    private function decryptElement(string $aesKey, string $encrypted, int $index): string
    {
        $doc = new \DOMDocument();
        $doc->loadXML($encrypted);

        $encryptedData = $doc->getElementsByTagName('EncryptedData')->item($index);

        $key = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
        $key->key = $aesKey;
        $enc = new XMLSecEnc();

        $enc->setNode($encryptedData);
        return $enc->decryptNode($key);
    }

    private function invoke(string $message): StatusResponse
    {
        $this->setupConfiguration();

        $xml = file_get_contents(__DIR__ . '/messages/' . $message . '.xml');

        return new StatusResponse($this->config, $xml);
    }
}
