<?php

declare(strict_types=1);

namespace BankId\Merchant\Enum;

enum TransactionStatus: string
{
    case Open = 'Open';
    case Pending = 'Pending';
    case Success = 'Success';
    case Failure = 'Failure';
    case Expired = 'Expired';
    case Cancelled = 'Cancelled';
}
