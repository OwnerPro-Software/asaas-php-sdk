<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

enum DocumentType: string
{
    case AllowBankAccountDepositStatement = 'ALLOW_BANK_ACCOUNT_DEPOSIT_STATEMENT';
    case Custom = 'CUSTOM';
    case EmancipationOfMinors = 'EMANCIPATION_OF_MINORS';
    case EntrepreneurRequirement = 'ENTREPRENEUR_REQUIREMENT';
    case IdentificationSelfie = 'IDENTIFICATION_SELFIE';
    case Identification = 'IDENTIFICATION';
    case Invoice = 'INVOICE';
    case MeiCertificate = 'MEI_CERTIFICATE';
    case MinutesOfConstitution = 'MINUTES_OF_CONSTITUTION';
    case MinutesOfElection = 'MINUTES_OF_ELECTION';
    case PowerOfAttorney = 'POWER_OF_ATTORNEY';
    case SocialContract = 'SOCIAL_CONTRACT';
}
