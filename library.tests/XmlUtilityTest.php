<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Exception\CommunicatorException;
use BankId\Merchant\Xml\XmlUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class XmlUtilityTest extends TestCase
{
    #[Test]
    public function parses_valid_xml(): void
    {
        $xml = '<root><child>value</child></root>';

        $result = XmlUtility::parse($xml);

        $this->assertInstanceOf(\SimpleXMLElement::class, $result);
        $this->assertSame('value', (string) $result->child);
    }

    #[Test]
    public function parses_xml_with_namespaces(): void
    {
        $xml = '<ns:root xmlns:ns="http://example.com"><ns:child>value</ns:child></ns:root>';

        $result = XmlUtility::parse($xml);

        $this->assertInstanceOf(\SimpleXMLElement::class, $result);
        $this->assertSame('value', (string) $result->child);
    }

    #[Test]
    public function throws_on_invalid_xml(): void
    {
        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('Failed to parse XML response');

        XmlUtility::parse('not xml at all');
    }

    #[Test]
    public function preserves_default_namespace_uri(): void
    {
        $xml = '<root xmlns="http://example.com/ns"><child>test</child></root>';

        $result = XmlUtility::parse($xml);

        $namespaces = $result->getNamespaces();
        $this->assertNotEmpty($namespaces);
    }
}
