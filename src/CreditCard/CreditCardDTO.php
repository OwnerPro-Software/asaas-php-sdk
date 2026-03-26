<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

use OwnerPro\Asaas\Support\BaseDTO;

final class CreditCardDTO extends BaseDTO
{
    public ?string $creditCardNumber = null;

    public ?string $creditCardBrand = null;

    public ?string $creditCardToken = null;
}
