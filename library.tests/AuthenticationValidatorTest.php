<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Enum\ServiceId;
use BankId\Merchant\Exception\CommunicatorException;
use BankId\Merchant\Response\SamlResponse;
use BankId\Merchant\Saml\SamlAttribute;
use BankId\Merchant\Validation\AuthenticationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationValidatorTest extends TestCase
{
    #[Test]
    public function validates_valid_authn_request(): void
    {
        $this->expectNotToPerformAssertions();

        AuthenticationValidator::validateAuthnRequest(
            'A1234567890',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            '',
            (string) ServiceId::ConsumerBin,
        );
    }

    #[Test]
    public function rejects_invalid_merchant_reference_starting_with_digit(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateAuthnRequest(
            '1invalid',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            '',
            (string) ServiceId::ConsumerBin,
        );
    }

    #[Test]
    public function rejects_merchant_reference_with_special_chars(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateAuthnRequest(
            'abc-def',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            '',
            (string) ServiceId::ConsumerBin,
        );
    }

    #[Test]
    public function rejects_merchant_reference_too_long(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateAuthnRequest(
            'A' . str_repeat('a', 35),
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            '',
            (string) ServiceId::ConsumerBin,
        );
    }

    #[Test]
    public function rejects_invalid_url(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateAuthnRequest(
            'Areference',
            'not a url',
            'nl:bvn:bankid:1.0:loa3',
            '',
            (string) ServiceId::ConsumerBin,
        );
    }

    #[Test]
    public function rejects_invalid_assurance_level(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateAuthnRequest(
            'Areference',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa2',
            '',
            (string) ServiceId::ConsumerBin,
        );
    }

    #[Test]
    public function rejects_negative_service_id(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateAuthnRequest(
            'Areference',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            '',
            '-1',
        );
    }

    #[Test]
    public function rejects_service_id_above_65535(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateAuthnRequest(
            'Areference',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            '',
            '65536',
        );
    }

    #[Test]
    public function sign_service_requires_consumer_bin(): void
    {
        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('ConsumerID BIN attribute should be present.');

        AuthenticationValidator::validateAuthnRequest(
            'Areference',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            'SomeDocument',
            (string) (ServiceId::Sign | ServiceId::Name),
        );
    }

    #[Test]
    public function sign_service_with_consumer_bin_and_document_id_passes(): void
    {
        $this->expectNotToPerformAssertions();

        AuthenticationValidator::validateAuthnRequest(
            'Areference',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            'SomeDocument',
            (string) (ServiceId::Sign | ServiceId::ConsumerBin),
        );
    }

    #[Test]
    public function document_id_without_sign_service_is_rejected(): void
    {
        $this->expectException(CommunicatorException::class);
        $this->expectExceptionMessage('DocumentID should not be filled');

        AuthenticationValidator::validateAuthnRequest(
            'Areference',
            'https://example.com/return',
            'nl:bvn:bankid:1.0:loa3',
            'SomeDocument',
            (string) ServiceId::ConsumerBin,
        );
    }

    #[Test]
    public function validates_null_expiration_period(): void
    {
        $this->expectNotToPerformAssertions();

        AuthenticationValidator::validateExpirationPeriod(null);
    }

    #[Test]
    public function validates_empty_expiration_period(): void
    {
        $this->expectNotToPerformAssertions();

        AuthenticationValidator::validateExpirationPeriod('');
    }

    #[Test]
    public function validates_valid_expiration_period(): void
    {
        $this->expectNotToPerformAssertions();

        AuthenticationValidator::validateExpirationPeriod('PT2M');
    }

    #[Test]
    public function rejects_expiration_period_too_long(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateExpirationPeriod('PT10M');
    }

    #[Test]
    public function rejects_invalid_expiration_period_format(): void
    {
        $this->expectException(CommunicatorException::class);

        AuthenticationValidator::validateExpirationPeriod('invalid');
    }

    #[Test]
    #[DataProvider('validSamlAttributeProvider')]
    public function validates_valid_saml_attributes(string $attrKey, string $attrValue): void
    {
        $this->expectNotToPerformAssertions();

        $samlResponse = self::buildSamlResponse([$attrKey => $attrValue]);
        AuthenticationValidator::validateSamlResponse($samlResponse);
    }

    #[Test]
    #[DataProvider('invalidSamlAttributeProvider')]
    public function rejects_invalid_saml_attributes(string $attrKey, string $attrValue): void
    {
        $this->expectException(CommunicatorException::class);

        $samlResponse = self::buildSamlResponse([$attrKey => $attrValue]);
        AuthenticationValidator::validateSamlResponse($samlResponse);
    }

    public static function validSamlAttributeProvider(): array
    {
        return [
            'ConsumerBin' => [SamlAttribute::ConsumerBin, 'NL1234567890'],
            'ConsumerTransientID' => [SamlAttribute::ConsumerTransientID, 'TRANSsomeid12345'],
            'ConsumerGender 0' => [SamlAttribute::ConsumerGender, '0'],
            'ConsumerGender 1' => [SamlAttribute::ConsumerGender, '1'],
            'ConsumerGender 2' => [SamlAttribute::ConsumerGender, '2'],
            'ConsumerGender 9' => [SamlAttribute::ConsumerGender, '9'],
            'ConsumerLegalLastName' => [SamlAttribute::ConsumerLegalLastName, 'van der Berg'],
            'ConsumerLegalLastNamePrefix' => [SamlAttribute::ConsumerLegalLastNamePrefix, 'van der'],
            'ConsumerFirstName' => [SamlAttribute::ConsumerFirstName, 'Jan'],
            'ConsumerInitials' => [SamlAttribute::ConsumerInitials, 'JK'],
            'ConsumerDateOfBirth' => [SamlAttribute::ConsumerDateOfBirth, '19900115'],
            'ConsumerStreet' => [SamlAttribute::ConsumerStreet, 'Kerkstraat'],
            'ConsumerHouseNo' => [SamlAttribute::ConsumerHouseNo, '42'],
            'ConsumerPostalCode' => [SamlAttribute::ConsumerPostalCode, '1234AB'],
            'ConsumerCity' => [SamlAttribute::ConsumerCity, 'Amsterdam'],
            'ConsumerCountry' => [SamlAttribute::ConsumerCountry, 'NL'],
            'ConsumerIban' => [SamlAttribute::ConsumerIban, 'NL91ABNA0417164300'],
            'ConsumerIs18OrOlder true' => [SamlAttribute::ConsumerIs18OrOlder, 'true'],
            'ConsumerIs18OrOlder false' => [SamlAttribute::ConsumerIs18OrOlder, 'false'],
            'ConsumerEmail' => [SamlAttribute::ConsumerEmail, 'test@example.com'],
            'ConsumerTelephone' => [SamlAttribute::ConsumerTelephone, '+31 6 12345678'],
        ];
    }

    public static function invalidSamlAttributeProvider(): array
    {
        return [
            'ConsumerBin no country prefix' => [SamlAttribute::ConsumerBin, '1234567890'],
            'ConsumerTransientID no TRANS prefix' => [SamlAttribute::ConsumerTransientID, 'noprefixid'],
            'ConsumerGender invalid' => [SamlAttribute::ConsumerGender, '5'],
            'ConsumerDateOfBirth bad month' => [SamlAttribute::ConsumerDateOfBirth, '19901315'],
            'ConsumerDateOfBirth bad format' => [SamlAttribute::ConsumerDateOfBirth, '1990-01-15'],
            'ConsumerHouseNo non-numeric' => [SamlAttribute::ConsumerHouseNo, 'abc'],
            'ConsumerPostalCode wrong format' => [SamlAttribute::ConsumerPostalCode, 'AB1234'],
            'ConsumerCountry three chars' => [SamlAttribute::ConsumerCountry, 'NLD'],
            'ConsumerIs18OrOlder yes' => [SamlAttribute::ConsumerIs18OrOlder, 'yes'],
            'ConsumerInitials lowercase' => [SamlAttribute::ConsumerInitials, 'jk'],
        ];
    }

    private static function buildSamlResponse(array $attributes): SamlResponse
    {
        $reflection = new \ReflectionClass(SamlResponse::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->attributes = $attributes;

        return $instance;
    }
}
