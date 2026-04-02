<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

enum CreditCardBrand: string
{
    case Visa = 'VISA';
    case Mastercard = 'MASTERCARD';
    case Elo = 'ELO';
    case Diners = 'DINERS';
    case Discover = 'DISCOVER';
    case Amex = 'AMEX';
    case Cabal = 'CABAL';
    case Banescard = 'BANESCARD';
    case Credz = 'CREDZ';
    case Sorocred = 'SOROCRED';
    case Credsystem = 'CREDSYSTEM';
    case Jcb = 'JCB';
    case Unknown = 'UNKNOWN';
}
