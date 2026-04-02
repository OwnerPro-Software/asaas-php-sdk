<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

enum PixQrCodeType: string
{
    case Static = 'STATIC';
    case Dynamic = 'DYNAMIC';
    case DynamicWithAsaasAddressKey = 'DYNAMIC_WITH_ASAAS_ADDRESS_KEY';
    case Composite = 'COMPOSITE';
}
