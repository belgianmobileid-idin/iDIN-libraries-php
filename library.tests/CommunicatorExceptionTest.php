<?php

declare(strict_types=1);

namespace BankId\Merchant\Tests;

use BankId\Merchant\Exception\CommunicatorException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CommunicatorExceptionTest extends TestCase
{
    #[Test]
    public function stores_message_and_code(): void
    {
        $ex = new CommunicatorException('Something failed', 42);

        $this->assertSame('Something failed', $ex->getMessage());
        $this->assertSame(42, $ex->getCode());
        $this->assertSame('Something failed', $ex->errorMessage);
        $this->assertSame('42', $ex->errorCode);
    }

    #[Test]
    public function handles_string_code(): void
    {
        $ex = new CommunicatorException('Error', 'AP2901');

        $this->assertSame('AP2901', $ex->errorCode);
        $this->assertSame(0, $ex->getCode());
    }

    #[Test]
    public function defaults_code_to_zero(): void
    {
        $ex = new CommunicatorException('Error');

        $this->assertSame('0', $ex->errorCode);
        $this->assertSame(0, $ex->getCode());
    }

    #[Test]
    public function to_xml_returns_valid_xml(): void
    {
        $ex = new CommunicatorException('Test error', 500);

        $xml = $ex->toXml();

        $this->assertStringContainsString('<errorCode>500</errorCode>', $xml);
        $this->assertStringContainsString('<errorMessage>Test error</errorMessage>', $xml);
        $this->assertStringContainsString('<Exception>', $xml);
        $this->assertStringContainsString('<Error>', $xml);
    }

    #[Test]
    public function is_exception_subclass(): void
    {
        $ex = new CommunicatorException('test');

        $this->assertInstanceOf(\Exception::class, $ex);
    }
}
