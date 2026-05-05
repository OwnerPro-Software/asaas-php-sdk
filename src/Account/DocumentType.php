<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

enum DocumentType: string
{
    case Identification = 'IDENTIFICATION';
    case SocialContract = 'SOCIAL_CONTRACT';
    case EntrepreneurRequirement = 'ENTREPRENEUR_REQUIREMENT';
    case Minutes = 'MINUTES';
    case Custom = 'CUSTOM';
}
