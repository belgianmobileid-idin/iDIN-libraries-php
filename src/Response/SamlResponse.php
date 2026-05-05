<?php

declare(strict_types=1);

namespace BankId\Merchant\Response;

use BankId\Merchant\Configuration\Configuration;
use BankId\Merchant\Exception\CommunicatorException;
use BankId\Merchant\Saml\SamlAttribute;
use BankId\Merchant\Xml\XmlUtility;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SamlResponse
{
    public string $transactionID = '';
    public string $merchantReference = '';
    public string $version = '';
    public string $acquirerID = '';
    public array $attributes = [];
    public ?SamlStatus $status = null;
    public array $attributesEncryptionKeys = [];

    private function __construct()
    {
    }

    public static function parse(Configuration $config, \SimpleXMLElement $responseDoc): self
    {
        $sr = new self();

        $response = $responseDoc->Transaction->container->children(XmlUtility::NS_PROTOCOL)->Response;
        $sr->transactionID = (string) $response->attributes()['ID'];
        $sr->merchantReference = (string) $response->attributes()['InResponseTo'];
        $sr->version = (string) $response->attributes()['Version'];
        $sr->acquirerID = (string) $responseDoc->Acquirer->acquirerID;

        foreach ($response->getNamespaces(true) as $nsvalue) {
            $children = $response->children($nsvalue);

            if ($nsvalue === XmlUtility::NS_PROTOCOL) {
                if (isset($children)) {
                    if (!isset($children->Status->StatusCode->StatusCode)) {
                        throw new CommunicatorException('Missing second level status code');
                    }
                    $sr->status = new SamlStatus(
                        (string) $children->Status->StatusMessage,
                        (string) $children->Status->StatusCode->attributes()['Value'],
                        (string) $children->Status->StatusCode->StatusCode->attributes()['Value']
                    );
                }
            } elseif ($nsvalue === XmlUtility::NS_ASSERTION) {
                if (!isset($children->Assertion)) {
                    continue;
                }

                $assertion = $children->Assertion;
                if (!isset($assertion->Subject)) {
                    continue;
                }

                $nameIdEncrypted = $assertion->Subject->EncryptedID;
                [$nameIdDecrypted, $nameIdAesKey] = $sr->decryptElement($config, $nameIdEncrypted);

                $sr->attributes = [];
                $nameId = new \SimpleXMLElement($nameIdDecrypted);
                if (str_starts_with((string) $nameId, 'TRANS')) {
                    $sr->attributes[SamlAttribute::ConsumerTransientID] = (string) $nameId;
                    $sr->attributesEncryptionKeys[] = ['AesKey' => $nameIdAesKey, 'AttributeName' => SamlAttribute::ConsumerTransientID];
                } else {
                    $sr->attributes[SamlAttribute::ConsumerBin] = (string) $nameId;
                    $sr->attributesEncryptionKeys[] = ['AesKey' => $nameIdAesKey, 'AttributeName' => SamlAttribute::ConsumerBin];
                }

                if (!isset($children->Assertion->AttributeStatement)) {
                    continue;
                }

                if (isset($children->Assertion->AttributeStatement->EncryptedAttribute)) {
                    foreach ($children->Assertion->AttributeStatement->EncryptedAttribute as $value) {
                        [$decrypted, $aesKey] = $sr->decryptElement($config, $value);
                        $element = new \SimpleXMLElement($decrypted);
                        $attributeName = (string) $element->attributes()['Name'];
                        $sr->attributes[$attributeName] = trim(dom_import_simplexml($element)->textContent);
                        $sr->attributesEncryptionKeys[] = ['AesKey' => $aesKey, 'AttributeName' => $attributeName];
                    }
                }

                if (isset($children->Assertion->AttributeStatement->Attribute)) {
                    foreach ($children->Assertion->AttributeStatement->Attribute as $value) {
                        $sr->attributes[(string) $value->attributes()['Name']] = (string) $value->AttributeValue;
                    }
                }
            }
        }

        return $sr;
    }

    private function decryptKey(Configuration $config, \DOMElement $encryptedElement): string
    {
        $encryptedKeyElement = $encryptedElement->getElementsByTagName('EncryptedKey')->item(0);
        $encryptedKey = XMLSecurityKey::fromEncryptedKeyElement($encryptedKeyElement);
        $encryptedKey->key = $config->merchantCertificate['pkey'];

        $enc = new XMLSecEnc();
        $enc->setNode($encryptedKeyElement);

        return $enc->decryptKey($encryptedKey);
    }

    /**
     * @return array{0: string, 1: string} [decryptedXml, aesKey]
     */
    private function decryptElement(Configuration $config, \SimpleXMLElement $encrypted): array
    {
        $encryptedElement = dom_import_simplexml($encrypted);

        $aesKey = $this->decryptKey($config, $encryptedElement);

        $encryptedData = $encryptedElement->getElementsByTagName('EncryptedData')->item(0);

        $key = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
        $key->key = $aesKey;
        $enc = new XMLSecEnc();

        $enc->setNode($encryptedData);

        return [$enc->decryptNode($key), $aesKey];
    }
}
