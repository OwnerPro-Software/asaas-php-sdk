<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

enum PixAddressKeyStatus: string
{
    case AwaitingActivation = 'AWAITING_ACTIVATION';
    case Active = 'ACTIVE';
    case AwaitingDeletion = 'AWAITING_DELETION';
    case AwaitingAccountDeletion = 'AWAITING_ACCOUNT_DELETION';
    case Deleted = 'DELETED';
    case Error = 'ERROR';
}
