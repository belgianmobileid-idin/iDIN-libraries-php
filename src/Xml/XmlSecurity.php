<?php

declare(strict_types=1);

namespace BankId\Merchant\Xml;

use BankId\Merchant\Configuration\Configuration;
use BankId\Merchant\Contract\LoggerInterface;
use BankId\Merchant\Exception\CommunicatorException;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class XmlSecurity
{
    private const BEGIN_CERTIFICATE = '-----BEGIN CERTIFICATE-----';
    private const END_CERTIFICATE = '-----END CERTIFICATE-----';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function addSignature(Configuration $config, string $xml): string
    {
        $this->logger->log('signing xml...');

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $signature = new XMLSecurityDSig('');
        $signature->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $signature->addReference(
            $doc,
            XMLSecurityDSig::SHA256,
            [
                'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
                'http://www.w3.org/2001/10/xml-exc-c14n#',
            ],
            ['force_uri' => true]
        );

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->passphrase = $config->merchantCertificatePassword;
        $key->loadKey($config->merchantCertificate['pkey']);

        $signature->sign($key);

        $fingerprint = $this->getFingerprint($config->merchantCertificate['cert']);
        $this->logger->log("embedding thumbprint: {{$fingerprint}}");

        $signature->appendToKeyInfo($signature->sigNode->ownerDocument->createElement('KeyName', $fingerprint));
        $signature->appendSignature($doc->documentElement);

        $this->logger->log('finished signing xml');

        return $doc->saveXML();
    }

    public function verifySignature(Configuration $config, string $xml): void
    {
        $this->logger->log('checking signature...');

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        if ($doc->getElementsByTagName('Signature')->length == 2) {
            $this->checkBankIdSignature($doc);
            $this->checkIdxSignature($config, $doc);
        } else {
            $this->checkIdxSignature($config, $doc);
        }

        $this->logger->log('signature verified');
    }

    private function checkBankIdSignature(\DOMDocument $doc): void
    {
        $bankidRoot = $doc->getElementsByTagNameNS(XmlUtility::NS_ASSERTION, 'Assertion')->item(0);
        $bankidDoc = new \DOMDocument();
        $root = $this->propagateNamespaces($bankidRoot, $bankidDoc);
        $bankidDoc->loadXML($bankidDoc->saveXML($root));

        $bankidDoc = $this->propagateInclusiveNamespaces($bankidRoot, $bankidDoc);

        $signature = new XMLSecurityDSig();
        $signature->locateSignature($bankidDoc);
        $signature->canonicalizeSignedInfo();
        $signature->idKeys = [0 => 'ID'];
        $signature->validateReference();

        $x509node = $doc->getElementsByTagName('X509Certificate')->item(0);
        if (!$x509node) {
            throw new CommunicatorException('Missing X509Certificate element in BankId signature');
        }
        $x509cert = $x509node->textContent;
        $signature->idKeys = [0 => 'ID'];

        $key = $signature->locateKey();
        $key->loadKey(Configuration::normalizeCertificate($x509cert), false, true);
        $signature->verify($key);
    }

    private function checkIdxSignature(Configuration $config, \DOMDocument $doc): void
    {
        $signature = new XMLSecurityDSig();
        $sig = $signature->locateSignature($doc, $doc->getElementsByTagName('Signature')->length - 1);
        if (!$sig) {
            $this->logger->log('Cannot locate Signature Node');
            throw new CommunicatorException('Cannot locate Signature Node');
        }

        $signature->canonicalizeSignedInfo();

        try {
            $signature->validateReference();
        } catch (\Exception $ex) {
            $this->logger->log('Reference Validation Failed');
            throw new CommunicatorException('Reference Validation Failed', $ex->getCode());
        }

        $key = $signature->locateKey();
        if (!$key) {
            $this->logger->log('Cannot locate the key');
            throw new CommunicatorException('Cannot locate the key.');
        }

        $key->loadKey($config->routingServiceCertificate);
        if ($signature->verify($key) == 1) {
            $this->logger->log('IDx signature is valid');
            return;
        }

        if (!empty($config->routingServiceCertificateAlternative)) {
            $key->loadKey($config->routingServiceCertificateAlternative);
            if ($signature->verify($key) == 1) {
                $this->logger->log('IDx signature is valid (alternative certificate)');
                return;
            }
        }

        $this->logger->log('The signature could not be verified');
        throw new CommunicatorException('The signature could not be verified.');
    }

    private function propagateNamespaces(\DOMNode $node, \DOMDocument $doc): \DOMElement
    {
        if (empty($node->lookupPrefix($node->namespaceURI))) {
            if ($node->hasAttribute('xmlns')) {
                $nd = $doc->createElement($node->localName);
                $nd->setAttribute('xmlns', $node->namespaceURI);
            } else {
                $ns = null;
                $n = $node;
                while ($n->parentNode !== null) {
                    if (empty($n->lookupPrefix($node->namespaceURI)) && $n->hasAttribute('xmlns')) {
                        $ns = $n->getAttribute('xmlns');
                        break;
                    }
                    $n = $n->parentNode;
                }
                $nd = $doc->createElement($node->localName);
                if ($ns !== null) {
                    $nd->setAttribute('xmlns', $ns);
                }
            }
        } else {
            $prefix = $node->lookupPrefix($node->namespaceURI);
            $nd = $doc->createElement($prefix . ':' . $node->localName);
            $nd->setAttribute('xmlns:' . $prefix, $node->namespaceURI);
        }

        foreach ($node->attributes as $value) {
            if (!empty($value->prefix)) {
                $nd->setAttribute($value->prefix . ':' . $value->localName, $value->value);
                $nd->setAttribute('xmlns:' . $value->prefix, $value->namespaceURI);
            } else {
                $nd->setAttribute($value->localName, $value->value);
            }
        }

        if (!$node->childNodes) {
            return $nd;
        }

        foreach ($node->childNodes as $child) {
            if ($child->nodeName === '#text') {
                $nd->appendChild($doc->createTextNode($child->nodeValue));
            } else {
                $nd->appendChild($this->propagateNamespaces($child, $doc));
            }
        }

        return $nd;
    }

    private function propagateInclusiveNamespaces(\DOMNode $srcDocument, \DOMDocument $document): \DOMDocument
    {
        $inclusiveNamespaceNodes = $document->getElementsByTagName('InclusiveNamespaces');
        foreach ($inclusiveNamespaceNodes as $node) {
            if (!empty($node->attributes->item(0)->nodeValue)) {
                $prefixes = explode(' ', $node->attributes->item(0)->nodeValue);
                foreach ($prefixes as $prefix) {
                    $document->documentElement->setAttributeNS(
                        XmlUtility::NS_URI,
                        'xmlns:' . $prefix,
                        $srcDocument->lookupNamespaceUri($prefix)
                    );
                }
            }
        }

        return $document;
    }

    private function getFingerprint(string $certificate): string
    {
        $contents = str_replace(self::END_CERTIFICATE, '', str_replace(self::BEGIN_CERTIFICATE, '', $certificate));
        $contents = base64_decode($contents);
        return strtoupper(sha1($contents));
    }
}
