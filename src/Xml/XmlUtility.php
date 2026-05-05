<?php

declare(strict_types=1);

namespace BankId\Merchant\Xml;

use BankId\Merchant\Contract\LoggerInterface;
use BankId\Merchant\Exception\CommunicatorException;
use BankId\Merchant\Logger\NullLogger;

class XmlUtility
{
    public const NS_BANKID = 'http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0';
    public const NS_ASSERTION = 'urn:oasis:names:tc:SAML:2.0:assertion';
    public const NS_PROTOCOL = 'urn:oasis:names:tc:SAML:2.0:protocol';
    public const NS_URI = 'http://www.w3.org/2000/xmlns/';

    public static function parse(string $xml, ?LoggerInterface $logger = null): \SimpleXMLElement
    {
        $logger ??= new NullLogger();

        libxml_use_internal_errors(true);
        $temp = simplexml_load_string($xml);
        if ($temp === false) {
            libxml_clear_errors();
            throw new CommunicatorException('Failed to parse XML response: ' . substr($xml, 0, 200));
        }

        $namespaceURI = '';
        $namespaces = $temp->getNamespaces();
        if (!empty($namespaces) && is_array($namespaces)) {
            $namespaceURI = array_values($namespaces)[0];
            $logger->Log("OriginalDocNamespace: {$namespaceURI}");
        }

        $xml = preg_replace("/(<\\/?)[\\w]*?:/m", '$1', $xml);
        $xml = preg_replace("/xmlns:[\\w]*?=\"[^\"]*?\"/m", '', $xml);

        $element = simplexml_load_string($xml);

        $namespaces = $element->getNamespaces();
        if (empty($namespaces) && !empty($namespaceURI)) {
            $element->addAttribute('xmlns', $namespaceURI);
            $logger->Log("Empty document namespace prevented, added default namespaceURI: {$namespaceURI}");
        }

        return $element;
    }
}
