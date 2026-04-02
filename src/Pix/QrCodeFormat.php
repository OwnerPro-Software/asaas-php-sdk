<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

enum QrCodeFormat: string
{
    case All = 'ALL';
    case Image = 'IMAGE';
    case Payload = 'PAYLOAD';
}
