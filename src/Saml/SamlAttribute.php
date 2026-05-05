<?php

declare(strict_types=1);

namespace BankId\Merchant\Saml;

class SamlAttribute
{
    public const DeliveredServiceID = 'urn:nl:bvn:bankid:1.0:bankid.deliveredserviceid';
    public const ConsumerBin = 'urn:nl:bvn:bankid:1.0:consumer.bin';
    public const ConsumerTransientID = 'urn:nl:bvn:bankid:1.0:consumer.transientid';
    public const ConsumerGender = 'urn:nl:bvn:bankid:1.0:consumer.gender';
    public const ConsumerLegalLastName = 'urn:nl:bvn:bankid:1.0:consumer.legallastname';
    public const ConsumerPrefLastName = 'urn:nl:bvn:bankid:1.0:consumer.preferredlastname';
    public const ConsumerPartnerLastName = 'urn:nl:bvn:bankid:1.0:consumer.partnerlastname';
    public const ConsumerLegalLastNamePrefix = 'urn:nl:bvn:bankid:1.0:consumer.legallastnameprefix';
    public const ConsumerPrefLastNamePrefix = 'urn:nl:bvn:bankid:1.0:consumer.preferredlastnameprefix';
    public const ConsumerInitials = 'urn:nl:bvn:bankid:1.0:consumer.initials';
    public const ConsumerDateOfBirth = 'urn:nl:bvn:bankid:1.0:consumer.dateofbirth';
    public const ConsumerStreet = 'urn:nl:bvn:bankid:1.0:consumer.street';
    public const ConsumerHouseNo = 'urn:nl:bvn:bankid:1.0:consumer.houseno';
    public const ConsumerHouseNoSuf = 'urn:nl:bvn:bankid:1.0:consumer.housenosuf';
    public const ConsumerAddressExtra = 'urn:nl:bvn:bankid:1.0:consumer.addressextra';
    public const ConsumerPostalCode = 'urn:nl:bvn:bankid:1.0:consumer.postalcode';
    public const ConsumerCity = 'urn:nl:bvn:bankid:1.0:consumer.city';
    public const ConsumerCountry = 'urn:nl:bvn:bankid:1.0:consumer.country';
    public const ConsumerDeprecatedBin = 'urn:nl:bvn:bankid:1.0:consumer.deprecatedbin';
    public const ConsumerFirstName = 'urn:nl:bvn:bankid:1.0:consumer.firstname';
    public const ConsumerIban = 'urn:nl:bvn:bankid:1.0:consumer.iban';
    public const ConsumerIs18OrOlder = 'urn:nl:bvn:bankid:1.0:consumer.18orolder';
    public const ConsumerEmail = 'urn:nl:bvn:bankid:1.0:consumer.email';
    public const ConsumerTelephone = 'urn:nl:bvn:bankid:1.0:consumer.telephone';
    public const ConsumerPartnerLastNamePrefix = 'urn:nl:bvn:bankid:1.0:consumer.partnerlastnameprefix';
    public const ConsumerIntAddressLine = 'urn:nl:bvn:bankid:1.0:consumer.intaddressline1/2/3';
    public const ConsumerIntAddressLine1 = 'urn:nl:bvn:bankid:1.0:consumer.intaddressline1';
    public const ConsumerIntAddressLine2 = 'urn:nl:bvn:bankid:1.0:consumer.intaddressline2';
    public const ConsumerIntAddressLine3 = 'urn:nl:bvn:bankid:1.0:consumer.intaddressline3';
    public const DocumentID = 'urn:nl:bvn:bankid:1.0:merchant.documentID';

    public function __construct(
        public readonly string $name,
        public readonly string $value,
    ) {
    }
}
