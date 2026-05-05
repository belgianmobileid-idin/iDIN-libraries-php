<?php

declare(strict_types=1);

namespace BankId\Merchant\Xml;

class AuthnRequestBuilder
{
    public function __construct(
        private readonly string $merchantID,
        private readonly string $merchantReturnUrl,
        private readonly string $merchantReference,
        private readonly string $requestedServiceID,
        private readonly string $assuranceLevel,
        private readonly string $documentID,
    ) {
    }

    public function toXml(): \DOMDocument
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $issueInstant = $now->format('Y-m-d\TH:i:s.v\Z');

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $authnRequest = $dom->createElementNS(XmlUtility::NS_PROTOCOL, 'samlp:AuthnRequest');
        $dom->appendChild($authnRequest);

        $authnRequest->setAttribute('AssertionConsumerServiceURL', $this->merchantReturnUrl);
        $authnRequest->setAttribute('AttributeConsumingServiceIndex', $this->requestedServiceID);
        $authnRequest->setAttribute('Consent', 'true');
        $authnRequest->setAttribute('ForceAuthn', 'true');
        $authnRequest->setAttribute('ID', $this->merchantReference);
        $authnRequest->setAttribute('IssueInstant', $issueInstant);
        $authnRequest->setAttribute('ProtocolBinding', 'nl:bvn:bankid:1.0:protocol:iDx');
        $authnRequest->setAttribute('Version', '2.0');

        $authnRequest->appendChild(
            $dom->createElementNS(XmlUtility::NS_ASSERTION, 'saml:Issuer', $this->merchantID)
        );

        if (!empty($this->documentID)) {
            $extensions = $dom->createElementNS(XmlUtility::NS_PROTOCOL, 'saml:Extensions', '');

            $attribute = $dom->createElementNS(XmlUtility::NS_ASSERTION, 'saml:Attribute', '');
            $attribute->setAttribute('Name', 'urn:nl:bvn:bankid:1.0:merchant.documentID');

            $attributeValue = $dom->createElementNS(XmlUtility::NS_ASSERTION, 'saml:AttributeValue', $this->documentID);

            $attribute->appendChild($attributeValue);
            $extensions->appendChild($attribute);
            $authnRequest->appendChild($extensions);
        }

        $authnRequest->appendChild(
            $dom->createElementNS(XmlUtility::NS_ASSERTION, 'saml:Conditions', '')
        );

        $requestedAuthnContext = $dom->createElementNS(XmlUtility::NS_PROTOCOL, 'RequestedAuthnContext', '');
        $requestedAuthnContext->setAttribute('Comparison', 'minimum');
        $requestedAuthnContext->appendChild(
            $dom->createElementNS(XmlUtility::NS_ASSERTION, 'saml:AuthnContextClassRef', $this->assuranceLevel)
        );
        $authnRequest->appendChild($requestedAuthnContext);

        $authnRequest->appendChild(
            $dom->createElementNS(XmlUtility::NS_PROTOCOL, 'Scoping', '')
        );

        $dom->formatOutput = true;
        return $dom;
    }
}
