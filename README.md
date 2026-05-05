# iDIN PHP Library

A PHP library for integrating with the iDIN identity verification protocol.

## Requirements

- PHP 8.3+
- Extensions: `dom`, `simplexml`, `curl`, `openssl`

## Installation

```bash
composer require currence/idin
```

## Configuration

Create a configuration from an XML file:

```php
use BankId\Merchant\Configuration\Configuration;

$config = Configuration::load('path/to/bankid-config.xml');
```

Or construct directly:

```php
$config = new Configuration(
    merchantID: '0123456789',
    merchantSubID: '0',
    merchantReturnUrl: 'https://my.shop/callback',
    acquirerDirectoryUrl: 'https://bank/directory',
    acquirerTransactionUrl: 'https://bank/transaction',
    acquirerStatusUrl: 'https://bank/status',
    merchantCertificateFile: 'certs/merchant.p12',
    merchantCertificatePassword: 'password',
    routingServiceCertificateFile: 'certs/routing.cer',
    routingServiceCertificateFileAlternative: '',
    samlCertificateFile: 'certs/saml.p12',
    samlCertificatePassword: 'password',
    logsEnabled: true,
    logsLocation: './logs',
    logsPattern: '%Y-%M-%D',
    logsFileName: 'iDIN.txt',
    serviceLogsEnabled: true,
    serviceLogsLocation: './logs',
    serviceLogsPattern: '%Y-%M-%D/%h%m%s.%f-%a.xml',
);
```

## Usage

### Get directory of issuers

```php
use BankId\Merchant\Communicator\Communicator;

$comm = new Communicator($config);
$response = $comm->getDirectory();

if (!$response->IsError) {
    foreach ($response->Issuers as $issuer) {
        echo $issuer->name . ' (' . $issuer->id . ')' . PHP_EOL;
    }
}
```

### Start authentication

```php
use BankId\Merchant\Request\AuthenticationRequest;
use BankId\Merchant\Enum\AssuranceLevel;

$auth = new AuthenticationRequest();
$auth->issuerID = 'INGBNL2A';
$auth->merchantReference = AuthenticationRequest::generateMerchantReference();
$auth->requestedServiceID = '21968';
$auth->assuranceLevel = AssuranceLevel::Loa3->value;
$auth->entranceCode = bin2hex(random_bytes(16));
$auth->language = 'nl';

$response = $comm->newAuthenticationRequest($auth);

if (!$response->IsError) {
    // Redirect consumer to the bank
    header('Location: ' . $response->IssuerAuthenticationURL);
}
```

### Check transaction status

```php
use BankId\Merchant\Enum\TransactionStatus;

$response = $comm->getResponse($transactionID);

if (!$response->IsError && $response->Status === TransactionStatus::Success) {
    $attributes = $response->SamlResponse->attributes;
    // Access consumer attributes
}
```

## Example website

An example web application is included in the `examples/` directory. To run it:

```bash
cd examples
php -S localhost:8080
```

Fill in the values in `examples/bankid-config.xml` before using.

## Project structure

```
src/
├── Communicator/       Main entry point
├── Configuration/      Configuration management
├── Contract/           Interfaces (LoggerInterface)
├── Enum/               Enums (TransactionStatus, AssuranceLevel, etc.)
├── Exception/          CommunicatorException
├── Logger/             Logger and NullLogger
├── Request/            AuthenticationRequest
├── Resources/          XSD schemas and CA certificate
├── Response/           Response classes and SAML handling
├── Saml/               SAML attribute constants
├── Validation/         Request and XML validation
└── Xml/                XML building, signing, and utilities
```

## Running tests

```bash
composer install
vendor/bin/phpunit
```

## License

Proprietary - see [LICENSE](LICENSE) for details.
