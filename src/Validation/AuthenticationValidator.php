<?php

declare(strict_types=1);

namespace BankId\Merchant\Validation;

use BankId\Merchant\Enum\ServiceId;
use BankId\Merchant\Exception\CommunicatorException;
use BankId\Merchant\Response\SamlResponse;
use BankId\Merchant\Saml\SamlAttribute;

class AuthenticationValidator
{
    private const DATE_TIMESTAMP = '[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}Z';
    private const MERCHANT_REFERENCE = '[a-zA-Z][a-zA-Z0-9]{0,34}';
    private const LOA = 'nl:bvn:bankid:1.0:loa3{1}';
    private const DOCUMENT_ID = '[a-zA-Z0-9]{0,256}';

    public static function validateAuthnRequest(
        string $merchantReference,
        string $merchantReturnUrl,
        string $assuranceLevel,
        string $documentID,
        string $serviceID,
    ): void {
        self::checkByValue($merchantReference, self::MERCHANT_REFERENCE);
        self::checkUrl($merchantReturnUrl);
        self::checkByValue($assuranceLevel, self::LOA);
        self::checkByValue($documentID, self::DOCUMENT_ID);
        self::checkBankIdServiceId($serviceID);
        self::checkSignService($documentID, $serviceID);
    }

    public static function validateExpirationPeriod(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        try {
            $now = new \DateTime();
            $then = new \DateTime();
            $then->add(new \DateInterval($value));
            if (($then->getTimestamp() - $now->getTimestamp()) > (5 * 60)) {
                throw new CommunicatorException('incorrect value: ' . $value);
            }
        } catch (\Exception $ex) {
            if ($ex instanceof CommunicatorException) {
                throw $ex;
            }
            throw new CommunicatorException('incorrect value: ' . $value);
        }
    }

    public static function validateSamlResponse(SamlResponse $samlResponse): void
    {
        $validationRules = [
            SamlAttribute::ConsumerBin => '[A-Za-z]{2}[[:print:]]{0,1024}',
            SamlAttribute::ConsumerTransientID => 'TRANS[[:print:]]{1,251}',
            SamlAttribute::ConsumerDeprecatedBin => '[A-Za-z]{2}[[:print:]]{0,1024}',
            SamlAttribute::ConsumerGender => '0|1|2|9',
            SamlAttribute::ConsumerLegalLastName => '[[:print:]]{1,200}',
            SamlAttribute::ConsumerLegalLastNamePrefix => '[[:print:]]{1,10}',
            SamlAttribute::ConsumerFirstName => '[[:print:]]{1,28}',
            SamlAttribute::ConsumerInitials => '[\p{Lu}]{1,24}',
            SamlAttribute::ConsumerDateOfBirth => '\d\d\d\d(0[0-9]|1[012])(0[0-9]|[12][0-9]|3[01])',
            SamlAttribute::ConsumerStreet => '[[:print:]]{1,43}',
            SamlAttribute::ConsumerHouseNo => '[0-9]{1,5}',
            SamlAttribute::ConsumerAddressExtra => '[[:print:]]{1,70}',
            SamlAttribute::ConsumerPostalCode => '[0-9]{4}[a-zA-Z]{2}',
            SamlAttribute::ConsumerCity => '[[:print:]]{1,24}',
            SamlAttribute::ConsumerCountry => '[a-zA-Z]{2}',
            SamlAttribute::ConsumerIban => '[0-9A-Z]{15,36}',
            SamlAttribute::ConsumerIs18OrOlder => 'true|false',
            SamlAttribute::ConsumerPrefLastName => '[[:print:]]{1,200}',
            SamlAttribute::ConsumerPartnerLastName => '[[:print:]]{1,200}',
            SamlAttribute::ConsumerPrefLastNamePrefix => '[[:print:]]{1,10}',
            SamlAttribute::ConsumerPartnerLastNamePrefix => '[[:print:]]{1,10}',
            SamlAttribute::ConsumerIntAddressLine1 => '[[:print:]]{1,70}',
            SamlAttribute::ConsumerIntAddressLine2 => '[[:print:]]{1,70}',
            SamlAttribute::ConsumerIntAddressLine3 => '[[:print:]]{1,70}',
            SamlAttribute::ConsumerTelephone => '[\d|[:space:]|\+|\-|\,|\(|\)]{1,20}',
            SamlAttribute::ConsumerEmail => '[[:print:]]{1,255}',
            SamlAttribute::DocumentID => '[[:print:]]{1,255}',
        ];

        foreach ($samlResponse->attributes as $attrKey => $attrValue) {
            if (isset($validationRules[$attrKey])) {
                self::checkByValue($attrValue, $validationRules[$attrKey]);
            }
        }
    }

    private static function checkByValue(string $value, string $regexp): void
    {
        if (preg_match('/^' . $regexp . '$/u', $value) === 0) {
            throw new CommunicatorException('incorrect value: ' . $value);
        }
    }

    private static function checkUrl(string $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return;
        }

        $pattern = "/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/";
        if (!preg_match($pattern, $value)) {
            throw new CommunicatorException('malformed url');
        }
    }

    private static function checkBankIdServiceId(string $value): void
    {
        $serviceId = (int) $value;

        if ($serviceId < 0 || $serviceId > 65535) {
            throw new CommunicatorException('incorrect value: ' . $value);
        }
    }

    private static function checkSignService(string $documentId, string $serviceId): void
    {
        $serviceIdInt = (int) $serviceId;

        if (!empty($documentId)) {
            if (($serviceIdInt & ServiceId::Sign) !== 0) {
                if (($serviceIdInt & ServiceId::ConsumerBin) === 0) {
                    throw new CommunicatorException('ConsumerID BIN attribute should be present.');
                }
            } else {
                if ($serviceIdInt !== ServiceId::Sign) {
                    throw new CommunicatorException('DocumentID should not be filled if the Sign service is not requested.');
                }
            }
        }
    }
}
