<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

enum TransferOperationType: string
{
    case Pix = 'PIX';
    case Ted = 'TED';
    case Internal = 'INTERNAL';
}
