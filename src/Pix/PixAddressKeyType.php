<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

enum PixAddressKeyType: string
{
    case Cpf = 'CPF';
    case Cnpj = 'CNPJ';
    case Email = 'EMAIL';
    case Phone = 'PHONE';
    case Evp = 'EVP';
}
