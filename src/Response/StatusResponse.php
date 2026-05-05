<?php

declare(strict_types=1);

namespace BankId\Merchant\Response;

use BankId\Merchant\Configuration\Configuration;
use BankId\Merchant\Enum\SamlStatusCode;
use BankId\Merchant\Enum\TransactionStatus;
use BankId\Merchant\Exception\CommunicatorException;
use BankId\Merchant\Xml\XmlUtility;

class StatusResponse
{
    public readonly bool $isError;
    public readonly ?ErrorResponse $error;
    public readonly ?string $transactionID;
    public readonly ?TransactionStatus $status;
    public readonly ?string $statusDateTimestamp;
    public readonly ?SamlResponse $samlResponse;
    public readonly string $rawMessage;

    public function __construct(Configuration $config, string $xml)
    {
        $this->rawMessage = $xml;

        if (!str_starts_with(trim($xml), '<')) {
            $this->isError = true;
            $this->error = ErrorResponse::fromMessage($xml);
            $this->transactionID = null;
            $this->status = null;
            $this->statusDateTimestamp = null;
            $this->samlResponse = null;
            return;
        }

        $res = new \SimpleXMLElement($xml);

        if ($res->getName() === 'AcquirerErrorRes') {
            $this->isError = true;
            $this->error = ErrorResponse::fromXml($res);
            $this->transactionID = null;
            $this->status = null;
            $this->statusDateTimestamp = null;
            $this->samlResponse = null;
            return;
        }

        $data = $res->children(XmlUtility::NS_BANKID);
        $this->transactionID = (string) $data->Transaction->transactionID;
        $this->status = TransactionStatus::from((string) $data->Transaction->status);

        if ($this->status === TransactionStatus::Success) {
            $this->statusDateTimestamp = (string) $data->Transaction->statusDateTimestamp;
            $samlResponse = SamlResponse::parse($config, $data);

            $this->samlResponse = $samlResponse;

            if ($samlResponse->status->statusCodeFirstLevel !== SamlStatusCode::Success->value) {
                $this->isError = true;
                $this->error = ErrorResponse::fromSamlStatus(new SamlStatus(
                    $samlResponse->status->statusMessage,
                    $samlResponse->status->statusCodeFirstLevel,
                    $samlResponse->status->statusCodeSecondLevel,
                ));
            } else {
                $this->isError = false;
                $this->error = null;
            }
        } else {
            if (isset($data->Transaction->container) && $data->Transaction->container->children() !== null) {
                throw new CommunicatorException('Response should not have a BankId signature.');
            }

            $this->isError = false;
            $this->error = null;
            $this->statusDateTimestamp = null;
            $this->samlResponse = null;
        }
    }
}
