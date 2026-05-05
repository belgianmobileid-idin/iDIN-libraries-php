<?php

declare(strict_types=1);

namespace BankId\Merchant\Communicator;

use BankId\Merchant\Configuration\Configuration;
use BankId\Merchant\Contract\LoggerInterface;
use BankId\Merchant\Exception\CommunicatorException;
use BankId\Merchant\Logger\Logger;
use BankId\Merchant\Request\AuthenticationRequest;
use BankId\Merchant\Response\AuthenticationResponse;
use BankId\Merchant\Response\DirectoryResponse;
use BankId\Merchant\Response\StatusResponse;
use BankId\Merchant\Validation\AuthenticationValidator;
use BankId\Merchant\Validation\XmlValidator;
use BankId\Merchant\Xml\AcquirerStatusRequest;
use BankId\Merchant\Xml\AcquirerTrxRequest;
use BankId\Merchant\Xml\AuthnRequestBuilder;
use BankId\Merchant\Xml\DirectoryRequest;
use BankId\Merchant\Xml\Merchant;
use BankId\Merchant\Xml\XmlSecurity;

class Communicator
{
    protected Configuration $configuration;
    protected LoggerInterface $logger;
    protected XmlSecurity $signer;

    public function __construct(?Configuration $configuration = null, ?LoggerInterface $logger = null)
    {
        $this->configuration = $configuration ?? Configuration::getDefault();
        $this->logger = $logger ?? new Logger($this->configuration);
        $this->signer = new XmlSecurity($this->logger);

        $this->logger->log(static::class . ' initialized');
    }

    public function getDirectory(): DirectoryResponse
    {
        $this->logger->log('sending new directory request');

        $directoryReq = new DirectoryRequest(
            new Merchant($this->configuration->merchantID, $this->configuration->merchantSubID)
        );

        $response = '';
        try {
            $this->logger->log('building idx message');
            $docTree = $directoryReq->toXml();

            $response = $this->performRequest($docTree, $this->configuration->acquirerDirectoryUrl);

            XmlValidator::isValidXml($response, XmlValidator::SCHEMA_IDX, $this->logger);
            $this->signer->verifySignature($this->configuration, $response);

            return new DirectoryResponse($response);
        } catch (\Exception $ex) {
            if (!empty($response) && str_starts_with(trim($response), '<')) {
                try {
                    return new DirectoryResponse($response);
                } catch (\Exception) {
                }
            }
            return new DirectoryResponse($ex->getMessage());
        }
    }

    public function newAuthenticationRequest(AuthenticationRequest $authRequest): AuthenticationResponse
    {
        $this->logger->log('sending new authentication request');

        $response = '';
        try {
            AuthenticationValidator::validateAuthnRequest(
                $authRequest->merchantReference,
                $this->configuration->merchantReturnUrl,
                $authRequest->assuranceLevel,
                $authRequest->documentID,
                $authRequest->requestedServiceID,
            );
            AuthenticationValidator::validateExpirationPeriod($authRequest->expirationPeriod);

            $this->logger->log('building idx message');

            $authnRequest = new AuthnRequestBuilder(
                $this->configuration->merchantID,
                $this->configuration->merchantReturnUrl,
                $authRequest->merchantReference,
                $authRequest->requestedServiceID,
                $authRequest->assuranceLevel,
                $authRequest->documentID,
            );
            $containedData = $authnRequest->toXml();

            $trxRequest = new AcquirerTrxRequest(
                new Merchant($this->configuration->merchantID, $this->configuration->merchantSubID),
                $this->configuration->merchantReturnUrl,
                $authRequest->issuerID,
                $authRequest->entranceCode,
                $authRequest->expirationPeriod,
                $authRequest->language,
                $containedData,
            );
            $docTree = $trxRequest->toXml();

            $response = $this->performRequest($docTree, $this->configuration->acquirerTransactionUrl);

            XmlValidator::isValidXml($response, XmlValidator::SCHEMA_IDX, $this->logger);
            $this->signer->verifySignature($this->configuration, $response);

            return new AuthenticationResponse($response);
        } catch (\Exception $ex) {
            if (!empty($response) && str_starts_with(trim($response), '<')) {
                try {
                    return new AuthenticationResponse($response);
                } catch (\Exception) {
                }
            }
            return new AuthenticationResponse($ex->getMessage());
        }
    }

    public function getResponse(string $transactionID): StatusResponse
    {
        $this->logger->log('sending new status request');

        $response = '';
        try {
            $this->logger->log('building idx message');

            $statusReq = new AcquirerStatusRequest(
                new Merchant($this->configuration->merchantID, $this->configuration->merchantSubID),
                $transactionID,
            );
            $docTree = $statusReq->toXml();

            $response = $this->performRequest($docTree, $this->configuration->acquirerStatusUrl);

            XmlValidator::isValidXml($response, XmlValidator::SCHEMA_IDX, $this->logger);
            $this->signer->verifySignature($this->configuration, $response);

            return new StatusResponse($this->configuration, $response);
        } catch (\Exception $ex) {
            if (!empty($response) && str_starts_with(trim($response), '<')) {
                try {
                    return new StatusResponse($this->configuration, $response);
                } catch (\Exception) {
                }
            }
            return new StatusResponse($this->configuration, $ex->getMessage());
        }
    }

    protected function performRequest(\DOMDocument $docTree, string $url): string
    {
        $this->configuration->ensureIsValid();
        $this->configuration->loadCertificates();

        $xml = $this->signer->addSignature($this->configuration, $docTree->saveXML());

        XmlValidator::isValidXml($xml, XmlValidator::SCHEMA_IDX, $this->logger);

        $this->logger->log("sending request to {{$url}}");
        $this->logger->logXmlMessage($xml, true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../Resources/cacert.pem');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: text/xml;charset=utf-8']);

        $data = curl_exec($ch);

        if ($data === false) {
            $error = curl_error($ch);
            $this->logger->log($error);
            throw new CommunicatorException($error);
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode >= 400) {
            throw new CommunicatorException('curl status code: ' . $statusCode);
        }

        $this->logger->logXmlMessage($data, true);
        $this->logger->log('Raw Response : ' . $data);

        return $data;
    }

    public static function getVersion(): string
    {
        return '1.3.0';
    }
}
