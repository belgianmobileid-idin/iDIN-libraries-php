<?php

declare(strict_types=1);

namespace BankId\Merchant\Validation;

use BankId\Merchant\Contract\LoggerInterface;
use BankId\Merchant\Exception\CommunicatorException;

class XmlValidator
{
    public const SCHEMA_IDX = 'SchemaIDX';

    private const SCHEMA_PATHS = [
        self::SCHEMA_IDX => __DIR__ . '/../Resources/schemas/idx.merchant-acquirer.1.0.xsd',
    ];

    public static function isValidXml(string $xml, string $schemaName, LoggerInterface $logger): bool
    {
        $schemaPath = self::SCHEMA_PATHS[$schemaName] ?? null;
        if ($schemaPath === null) {
            throw new CommunicatorException("Unknown schema: {$schemaName}");
        }

        libxml_use_internal_errors(true);

        $document = new \DOMDocument();
        $document->loadXML($xml);

        if (!$document->schemaValidate($schemaPath)) {
            foreach (libxml_get_errors() as $error) {
                $logger->log("xml schema is not valid: {{$error->message}}");
                throw new CommunicatorException($error->message, $error->code);
            }
        }

        $logger->log('xml schema is valid');
        return true;
    }
}
