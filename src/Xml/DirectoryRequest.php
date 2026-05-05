<?php

declare(strict_types=1);

namespace BankId\Merchant\Xml;

class DirectoryRequest
{
    private const VERSION = '1.0.0';
    private const PRODUCT_ID = 'NL:BVN:BankID:1.0';

    public function __construct(
        private readonly Merchant $merchant,
    ) {
    }

    public function toXml(): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');

        $directoryReq = $dom->createElementNS(XmlUtility::NS_BANKID, 'DirectoryReq');
        $directoryReq->setAttribute('productID', self::PRODUCT_ID);
        $directoryReq->setAttribute('version', self::VERSION);
        $dom->appendChild($directoryReq);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $directoryReq->appendChild(
            $dom->createElementNS(XmlUtility::NS_BANKID, 'createDateTimestamp', $now->format('Y-m-d\TH:i:s.v\Z'))
        );

        $merchant = $dom->createElementNS(XmlUtility::NS_BANKID, 'Merchant');
        $merchant->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'merchantID', $this->merchant->merchantID));
        $merchant->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'subID', $this->merchant->subID));
        $directoryReq->appendChild($merchant);

        $dom->formatOutput = true;
        return $dom;
    }
}
