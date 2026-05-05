<?php

declare(strict_types=1);

namespace BankId\Merchant\Response;

use BankId\Merchant\Xml\XmlUtility;

class DirectoryResponse
{
    public readonly bool $isError;
    public readonly ?ErrorResponse $error;
    public readonly ?string $directoryDateTimestamp;
    /** @var Issuer[] */
    public readonly array $issuers;
    public readonly string $rawMessage;

    public function __construct(string $xml)
    {
        $this->rawMessage = $xml;

        if (!str_starts_with(trim($xml), '<')) {
            $this->isError = true;
            $this->error = ErrorResponse::fromMessage($xml);
            $this->directoryDateTimestamp = null;
            $this->issuers = [];
            return;
        }

        $res = new \SimpleXMLElement($xml);

        if ($res->getName() === 'AcquirerErrorRes') {
            $this->isError = true;
            $this->error = ErrorResponse::fromXml($res);
            $this->directoryDateTimestamp = null;
            $this->issuers = [];
            return;
        }

        $data = $res->children(XmlUtility::NS_BANKID);
        $this->directoryDateTimestamp = (string) $data->Directory->directoryDateTimestamp;

        $issuers = [];
        foreach ($data->Directory->Country as $c) {
            foreach ($c->Issuer as $i) {
                $issuers[] = new Issuer(
                    (string) $c->countryNames,
                    (string) $i->issuerName,
                    (string) $i->issuerID,
                );
            }
        }

        $this->isError = false;
        $this->error = null;
        $this->issuers = $issuers;
    }

    /**
     * @return array<string, Issuer[]>
     */
    public function getIssuersByCountry(): array
    {
        $result = [];
        foreach ($this->issuers as $issuer) {
            $result[$issuer->country][] = $issuer;
        }
        return $result;
    }
}
