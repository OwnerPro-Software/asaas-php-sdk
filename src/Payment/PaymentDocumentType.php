<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

enum PaymentDocumentType: string
{
    case Invoice = 'INVOICE';
    case Contract = 'CONTRACT';
    case Media = 'MEDIA';
    case Document = 'DOCUMENT';
    case Spreadsheet = 'SPREADSHEET';
    case Program = 'PROGRAM';
    case Other = 'OTHER';
}
