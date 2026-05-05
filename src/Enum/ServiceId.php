<?php

declare(strict_types=1);

namespace BankId\Merchant\Enum;

final class ServiceId
{
    public const None = 0;
    public const ConsumerTransientId = 0;
    public const ConsumerBin = 16384;
    public const Name = 4096;
    public const Address = 1024;
    public const IsEighteenOrOlder = 64;
    public const DateOfBirth = 448;
    public const Gender = 16;
    public const BSN = 1;
    public const Email = 2;
    public const Telephone = 4;
    public const Sign = 8;

    private function __construct()
    {
    }
}
