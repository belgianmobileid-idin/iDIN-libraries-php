<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Enum\AssuranceLevel;
use BankId\Merchant\Enum\ServiceId;
use BankId\Merchant\Request\AuthenticationRequest;
use BankId\Merchant\Xml\AcquirerTrxRequest;
use BankId\Merchant\Xml\AuthnRequestBuilder;
use BankId\Merchant\Xml\Merchant;
use BankId\Merchant\Xml\XmlSecurity;
use BankId\Merchant\Logger\NullLogger;
use PHPUnit\Framework\Attributes\Test;

class XmlValidationTest extends BaseTest
{
    #[Test]
    public function should_generate_valid_xml_request(): void
    {
        $this->setupConfiguration();
        $this->config->merchantSubID = '42';

        $auth = new AuthenticationRequest();
        $auth->assuranceLevel = AssuranceLevel::Loa3->value;
        $auth->entranceCode = '1';
        $auth->expirationPeriod = 'PT2M';
        $auth->issuerID = 'INGBNL2A';
        $auth->language = 'nl';
        $auth->merchantReference = AuthenticationRequest::generateMerchantReference();
        $auth->requestedServiceID = (string) (ServiceId::Sign | ServiceId::ConsumerBin);
        $auth->documentID = 'SomeDocumentID';

        $authnRequest = new AuthnRequestBuilder(
            $this->config->merchantID,
            $this->config->merchantReturnUrl,
            $auth->merchantReference,
            $auth->requestedServiceID,
            $auth->assuranceLevel,
            $auth->documentID,
        );
        $containedData = $authnRequest->toXml();

        $trxRequest = new AcquirerTrxRequest(
            new Merchant($this->config->merchantID, $this->config->merchantSubID),
            $this->config->merchantReturnUrl,
            $auth->issuerID,
            $auth->entranceCode,
            $auth->expirationPeriod,
            $auth->language,
            $containedData,
        );
        $docTree = $trxRequest->toXml();

        $signer = new XmlSecurity(new NullLogger());
        $signedXml = $signer->addSignature($this->config, $docTree->saveXML());

        $this->assertNotNull($signedXml);

        $valid = $this->validate($signedXml);
        $this->assertTrue($valid);
    }

    private function validate(string $xmlstring): bool
    {
        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($xmlstring);

        if (!$xml->schemaValidate(__DIR__ . '/schemas/all.xsd')) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                echo "Schema validation error: {$error->message} on line {$error->line}\n";
            }
            libxml_clear_errors();
            return false;
        }
        return true;
    }
}
