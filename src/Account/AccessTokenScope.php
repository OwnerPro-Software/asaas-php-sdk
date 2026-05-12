<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

enum AccessTokenScope: string
{
    case Read = 'READ';
    case ReadWrite = 'READ_WRITE';
}
