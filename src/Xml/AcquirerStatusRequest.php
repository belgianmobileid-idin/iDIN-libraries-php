<?php

declare(strict_types=1);

namespace BankId\Merchant\Xml;

class AcquirerStatusRequest
{
    private const VERSION = '1.0.0';
    private const PRODUCT_ID = 'NL:BVN:BankID:1.0';

    public function __construct(
        private readonly Merchant $merchant,
        private readonly string $transactionID,
    ) {
    }

    public function toXml(): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');

        $acquirerStatusReq = $dom->createElementNS(XmlUtility::NS_BANKID, 'AcquirerStatusReq');
        $acquirerStatusReq->setAttribute('productID', self::PRODUCT_ID);
        $acquirerStatusReq->setAttribute('version', self::VERSION);
        $dom->appendChild($acquirerStatusReq);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $acquirerStatusReq->appendChild(
            $dom->createElementNS(XmlUtility::NS_BANKID, 'createDateTimestamp', $now->format('Y-m-d\TH:i:s.v\Z'))
        );

        $merchant = $dom->createElementNS(XmlUtility::NS_BANKID, 'Merchant');
        $merchant->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'merchantID', $this->merchant->merchantID));
        $merchant->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'subID', $this->merchant->subID));
        $acquirerStatusReq->appendChild($merchant);

        $transaction = $dom->createElementNS(XmlUtility::NS_BANKID, 'Transaction');
        $transaction->appendChild($dom->createElementNS(XmlUtility::NS_BANKID, 'transactionID', $this->transactionID));
        $acquirerStatusReq->appendChild($transaction);

        $dom->formatOutput = true;
        return $dom;
    }
}
