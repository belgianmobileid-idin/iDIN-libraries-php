<?php

declare(strict_types=1);

namespace BankId\Merchant\Exception;

class CommunicatorException extends \Exception
{
    public readonly string $errorCode;
    public readonly string $errorMessage;

    public function __construct(string $message, int|string $code = 0)
    {
        $this->errorMessage = $message;
        $this->errorCode = (string) $code;

        parent::__construct($message, is_int($code) ? $code : 0);
    }

    public function toXml(): string
    {
        return "
            <Exception>
                <Error>
                    <errorCode>{$this->errorCode}</errorCode>
                    <errorMessage>{$this->errorMessage}</errorMessage>
                </Error>
            </Exception>
        ";
    }
}
