<?php

declare(strict_types=1);

namespace BankId\Merchant\Xml;

class AcquirerTrxRequest
{
    private const VERSION = '1.0.0';
    private const PRODUCT_ID = 'NL:BVN:BankID:1.0';

    public function __construct(
        private readonly Merchant $merchant,
        private readonly string $merchantReturnUrl,
        private readonly string $issuerID,
        private readonly string $entranceCode,
        private readonly ?string $expirationPeriod,
        private readonly ?string $language,
        private readonly \DOMDocument $containedData,
    ) {
    }

    public function toXml(): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');

        $acquirerTrxReq = $dom->createElementNS(XmlUtility::NS_BANKID, 'AcquirerTrxReq');
        $acquirerTrxReq->setAttribute('productID', self::PRODUCT_ID);
        $acquirerTrxReq->setAttribute('version', self::VERSION);
        $dom->appendChild($acquirerTrxReq);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $acquirerTrxReq->appendChild(
            $dom->createElementNS(XmlUtility::NS_BANKID, 'createDateTimestamp', $now->format('Y-m-d\TH:i:s.v\Z'))
        );

        $issuer = $dom->createElementNS(XmlUtility::NS_BANKID, 'Issuer');
        $issuer->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'issuerID', $this->issuerID));
        $acquirerTrxReq->appendChild($issuer);

        $merchantEl = $dom->createElementNS(XmlUtility::NS_BANKID, 'Merchant');
        $merchantEl->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'merchantID', $this->merchant->merchantID));
        $merchantEl->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'subID', $this->merchant->subID));
        $merchantReturnURLEl = $dom->createElement('merchantReturnURL');
        $merchantReturnURLEl->appendChild($dom->createTextNode($this->merchantReturnUrl));
        $merchantEl->appendChild($merchantReturnURLEl);
        $acquirerTrxReq->appendChild($merchantEl);

        $transaction = $dom->createElementNS(XmlUtility::NS_BANKID, 'Transaction');
        if ($this->expirationPeriod) {
            $transaction->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'expirationPeriod', $this->expirationPeriod));
        }
        if ($this->language) {
            $transaction->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'language', $this->language));
        }
        $transaction->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'entranceCode', $this->entranceCode));
        $acquirerTrxReq->appendChild($transaction);

        $container = $dom->createElementNS(XmlUtility::NS_BANKID, 'container');
        $transaction->appendChild($container);

        $authnRequestNode = $this->containedData->documentElement;
        $container->appendChild($dom->importNode($authnRequestNode, true));

        $dom->formatOutput = true;
        return $dom;
    }
}
