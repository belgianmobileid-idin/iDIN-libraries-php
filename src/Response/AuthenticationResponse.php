<?php

declare(strict_types=1);

namespace BankId\Merchant\Response;

use BankId\Merchant\Xml\XmlUtility;

class AuthenticationResponse
{
    public readonly bool $isError;
    public readonly ?ErrorResponse $error;
    public readonly ?string $issuerAuthenticationURL;
    public readonly ?string $transactionID;
    public readonly ?string $transactionCreateDateTimestamp;
    public readonly string $rawMessage;

    public function __construct(string $xml)
    {
        $this->rawMessage = $xml;

        if (!str_starts_with(trim($xml), '<')) {
            $this->isError = true;
            $this->error = ErrorResponse::fromMessage($xml);
            $this->issuerAuthenticationURL = null;
            $this->transactionID = null;
            $this->transactionCreateDateTimestamp = null;
            return;
        }

        $res = new \SimpleXMLElement($xml);

        if ($res->getName() === 'AcquirerErrorRes') {
            $this->isError = true;
            $this->error = ErrorResponse::fromXml($res);
            $this->issuerAuthenticationURL = null;
            $this->transactionID = null;
            $this->transactionCreateDateTimestamp = null;
            return;
        }

        $data = $res->children(XmlUtility::NS_BANKID);
        $this->isError = false;
        $this->error = null;
        $this->issuerAuthenticationURL = (string) $data->Issuer->issuerAuthenticationURL;
        $this->transactionCreateDateTimestamp = (string) $data->Transaction->transactionCreateDateTimestamp;
        $this->transactionID = (string) $data->Transaction->transactionID;
    }
}
