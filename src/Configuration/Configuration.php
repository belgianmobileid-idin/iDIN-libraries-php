<?php

declare(strict_types=1);

namespace BankId\Merchant\Configuration;

class Configuration
{
    public string $merchantID;
    public string $merchantSubID;
    public string $merchantReturnUrl;

    public string $acquirerDirectoryUrl;
    public string $acquirerTransactionUrl;
    public string $acquirerStatusUrl;

    public string $merchantCertificateFile;
    public readonly string $merchantCertificatePassword;
    public string $routingServiceCertificateFile;
    public string $routingServiceCertificateFileAlternative;
    public string $samlCertificateFile;
    public readonly string $samlCertificatePassword;

    public ?array $merchantCertificate = null;
    public string $routingServiceCertificate = '';
    public string $routingServiceCertificateAlternative = '';
    public ?array $samlCertificate = null;

    public bool $logsEnabled;
    public string $logsLocation;
    public string $logsPattern;
    public string $logsFileName;

    public bool $serviceLogsEnabled;
    public string $serviceLogsLocation;
    public string $serviceLogsPattern;

    public function __construct(
        string $merchantID,
        string $merchantSubID,
        string $merchantReturnUrl,
        string $acquirerDirectoryUrl,
        string $acquirerTransactionUrl,
        string $acquirerStatusUrl,
        string $merchantCertificateFile,
        string $merchantCertificatePassword,
        string $routingServiceCertificateFile,
        string $routingServiceCertificateFileAlternative,
        string $samlCertificateFile,
        string $samlCertificatePassword,
        bool $logsEnabled,
        string $logsLocation,
        string $logsPattern,
        string $logsFileName,
        bool $serviceLogsEnabled,
        string $serviceLogsLocation,
        string $serviceLogsPattern,
        mixed $merchantCertificate = null,
        string $routingServiceCertificate = '',
        string $routingServiceCertificateAlternative = '',
        mixed $samlCertificate = null,
    ) {
        $this->merchantID = $merchantID;
        $this->merchantSubID = $merchantSubID;
        $this->merchantReturnUrl = $merchantReturnUrl;
        $this->acquirerDirectoryUrl = $acquirerDirectoryUrl;
        $this->acquirerTransactionUrl = $acquirerTransactionUrl;
        $this->acquirerStatusUrl = $acquirerStatusUrl;
        $this->merchantCertificateFile = $merchantCertificateFile;
        $this->merchantCertificatePassword = $merchantCertificatePassword;
        $this->routingServiceCertificateFile = $routingServiceCertificateFile;
        $this->routingServiceCertificateFileAlternative = $routingServiceCertificateFileAlternative;
        $this->samlCertificateFile = $samlCertificateFile;
        $this->samlCertificatePassword = $samlCertificatePassword;
        $this->logsEnabled = $logsEnabled;
        $this->logsLocation = $logsLocation;
        $this->logsPattern = $logsPattern;
        $this->logsFileName = $logsFileName;
        $this->serviceLogsEnabled = $serviceLogsEnabled;
        $this->serviceLogsLocation = $serviceLogsLocation;
        $this->serviceLogsPattern = $serviceLogsPattern;

        if ($merchantCertificate !== null) {
            $this->merchantCertificate = $merchantCertificate;
        }
        if ($routingServiceCertificate !== '') {
            $this->routingServiceCertificate = $routingServiceCertificate;
        }
        if ($routingServiceCertificateAlternative !== '') {
            $this->routingServiceCertificateAlternative = $routingServiceCertificateAlternative;
        }
        if ($samlCertificate !== null) {
            $this->samlCertificate = $samlCertificate;
        }

        $this->loadCertificates();
    }

    public static function getDefault(): self
    {
        $configPath = dirname(__FILE__) . '/../../../config/iDINConfig.php';
        if (file_exists($configPath)) {
            require_once $configPath;
        }

        global $idin_config_params;

        return new self(
            $idin_config_params['BankId.Merchant.MerchantID'],
            $idin_config_params['BankId.Merchant.SubID'],
            $idin_config_params['BankId.Merchant.ReturnUrl'],
            $idin_config_params['BankId.Acquirer.DirectoryUrl'],
            $idin_config_params['BankId.Acquirer.TransactionUrl'],
            $idin_config_params['BankId.Acquirer.StatusUrl'],
            $idin_config_params['BankId.Merchant.Certificate.File'],
            $idin_config_params['BankId.Merchant.Certificate.Password'],
            $idin_config_params['BankId.RoutingService.Certificate.File'],
            $idin_config_params['BankId.RoutingService.Certificate.File.Alternative'] ?? '',
            $idin_config_params['BankId.SAML.Certificate.File'],
            $idin_config_params['BankId.SAML.Certificate.Password'],
            (bool) ($idin_config_params['BankId.Logs.Enabled'] ?? false),
            $idin_config_params['BankId.Logs.Location'] ?? '',
            $idin_config_params['BankId.Logs.Pattern'] ?? '%Y-%M-%D/',
            $idin_config_params['BankId.Logs.FileName'] ?? 'iDIN.txt',
            (bool) ($idin_config_params['BankId.ServiceLogs.Enabled'] ?? false),
            $idin_config_params['BankId.ServiceLogs.Location'] ?? '',
            $idin_config_params['BankId.ServiceLogs.Pattern'] ?? '%Y-%M-%D/%h%m%s.%f-%a.xml',
        );
    }

    public static function load(string $configXml): self
    {
        $xml = new \SimpleXMLElement(file_get_contents($configXml));
        $params = [];

        foreach ($xml->appSettings->add as $item) {
            $params[(string) $item['key']] = (string) $item['value'];
        }

        return new self(
            $params['BankId.Merchant.MerchantID'] ?? '',
            $params['BankId.Merchant.SubID'] ?? '0',
            $params['BankId.Merchant.ReturnUrl'] ?? '',
            $params['BankId.Acquirer.DirectoryUrl'] ?? '',
            $params['BankId.Acquirer.TransactionUrl'] ?? '',
            $params['BankId.Acquirer.StatusUrl'] ?? '',
            $params['BankId.Merchant.Certificate.File'] ?? '',
            $params['BankId.Merchant.Certificate.Password'] ?? '',
            $params['BankId.RoutingService.Certificate.File'] ?? '',
            $params['BankId.RoutingService.Certificate.File.Alternative'] ?? '',
            $params['BankId.SAML.Certificate.File'] ?? '',
            $params['BankId.SAML.Certificate.Password'] ?? '',
            (bool) ($params['BankId.Logs.Enabled'] ?? false),
            $params['BankId.Logs.Location'] ?? '',
            $params['BankId.Logs.Pattern'] ?? '%Y-%M-%D/',
            $params['BankId.Logs.FileName'] ?? 'iDIN.txt',
            (bool) ($params['BankId.ServiceLogs.Enabled'] ?? false),
            $params['BankId.ServiceLogs.Location'] ?? '',
            $params['BankId.ServiceLogs.Pattern'] ?? '%Y-%M-%D/%h%m%s.%f-%a.xml',
        );
    }

    public function loadCertificates(): void
    {
        if (!empty($this->merchantCertificateFile) && $this->merchantCertificate === null) {
            $pkcs12 = file_get_contents($this->merchantCertificateFile);
            if ($pkcs12 !== false) {
                $certs = [];
                if (!openssl_pkcs12_read($pkcs12, $certs, $this->merchantCertificatePassword)) {
                    throw new \BankId\Merchant\Exception\CommunicatorException(
                        'Failed to read merchant PKCS12 certificate: ' . openssl_error_string()
                    );
                }
                $this->merchantCertificate = $certs;
            }
        }

        if (!empty($this->routingServiceCertificateFile) && $this->routingServiceCertificate === '') {
            $content = file_get_contents($this->routingServiceCertificateFile);
            if ($content !== false) {
                $this->routingServiceCertificate = self::normalizeCertificate($content);
            }
        }

        if (!empty($this->routingServiceCertificateFileAlternative) && $this->routingServiceCertificateAlternative === '') {
            if (file_exists($this->routingServiceCertificateFileAlternative)) {
                $content = file_get_contents($this->routingServiceCertificateFileAlternative);
                if ($content !== false) {
                    $this->routingServiceCertificateAlternative = self::normalizeCertificate($content);
                }
            }
        }

        if (!empty($this->samlCertificateFile) && $this->samlCertificate === null) {
            $pkcs12 = file_get_contents($this->samlCertificateFile);
            if ($pkcs12 !== false) {
                $certs = [];
                if (!openssl_pkcs12_read($pkcs12, $certs, $this->samlCertificatePassword)) {
                    throw new \BankId\Merchant\Exception\CommunicatorException(
                        'Failed to read SAML PKCS12 certificate: ' . openssl_error_string()
                    );
                }
                $this->samlCertificate = $certs;
            }
        }
    }

    public function ensureIsValid(): void
    {
        $required = [
            'merchantID' => $this->merchantID ?? '',
            'merchantReturnUrl' => $this->merchantReturnUrl ?? '',
            'acquirerDirectoryUrl' => $this->acquirerDirectoryUrl ?? '',
            'acquirerTransactionUrl' => $this->acquirerTransactionUrl ?? '',
            'acquirerStatusUrl' => $this->acquirerStatusUrl ?? '',
        ];

        foreach ($required as $name => $value) {
            if ($value === '') {
                throw new \BankId\Merchant\Exception\CommunicatorException(
                    'The configuration parameter is not configured: ' . $name
                );
            }
        }
    }

    public static function normalizeCertificate(string $cert): string
    {
        $cert = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $cert = str_replace('-----END CERTIFICATE-----', '', $cert);
        $cert = str_replace(["\r\n", "\n", "\r", " "], '', $cert);

        return "-----BEGIN CERTIFICATE-----\n"
            . chunk_split($cert, 64, "\n")
            . "-----END CERTIFICATE-----\n";
    }

    public function getVersion(): string
    {
        return '1.3.0';
    }
}
