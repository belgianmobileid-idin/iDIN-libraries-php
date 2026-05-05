<?php

declare(strict_types=1);

namespace BankId\Merchant\Enum;

enum SamlStatusCode: string
{
    case Success = 'urn:oasis:names:tc:SAML:2.0:status:Success';
    case Requester = 'urn:oasis:names:tc:SAML:2.0:status:Requester';
    case RequestDenied = 'urn:oasis:names:tc:SAML:2.0:status:RequestDenied';
    case RequestUnsupported = 'urn:oasis:names:tc:SAML:2.0:status:RequestUnsupported';
    case InvalidAttrNameOrValue = 'urn:oasis:names:tc:SAML:2.0:status:InvalidAttrNameOrValue';
    case MismatchWithIDx = 'urn:nl:bvn:bankid:1.0:status:MismatchWithIDx';
}
