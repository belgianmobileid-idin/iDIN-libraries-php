<?php

declare(strict_types=1);

namespace BankId\Merchant\Response;

use BankId\Merchant\Xml\XmlUtility;

class ErrorResponse
{
    public function __construct(
        public readonly string $errorCode = '',
        public readonly string $errorMessage = '',
        public readonly string $errorDetails = '',
        public readonly string $suggestedAction = '',
        public readonly string $consumerMessage = '',
        public readonly ?SamlStatus $additionalInformation = null,
    ) {
    }

    public static function fromXml(\SimpleXMLElement $errRes): self
    {
        $children = $errRes->children(XmlUtility::NS_BANKID);

        $additionalInformation = null;
        if (isset($children->Error->container)) {
            $response = $children->Error->container->children(XmlUtility::NS_PROTOCOL)->Response;
            $additionalInformation = new SamlStatus(
                (string) $response->Status->StatusMessage,
                (string) $response->Status->StatusCode->attributes()['Value'],
                (string) $response->Status->StatusCode->StatusCode->attributes()['Value']
            );
        }

        return new self(
            errorCode: (string) $children->Error->errorCode,
            errorMessage: (string) $children->Error->errorMessage,
            errorDetails: (string) $children->Error->errorDetail,
            suggestedAction: (string) $children->Error->suggestedAction,
            consumerMessage: (string) $children->Error->consumerMessage,
            additionalInformation: $additionalInformation,
        );
    }

    public static function fromException(\Exception $exception): self
    {
        return new self(
            errorMessage: $exception->getMessage(),
            errorDetails: ($exception->getPrevious() !== null)
                ? $exception->getPrevious()->getMessage()
                : '',
        );
    }

    public static function fromSamlStatus(SamlStatus $samlStatus): self
    {
        return new self(
            errorMessage: 'SAML specific error.',
            additionalInformation: $samlStatus,
        );
    }

    public static function fromMessage(string $message): self
    {
        return new self(errorMessage: $message);
    }
}
