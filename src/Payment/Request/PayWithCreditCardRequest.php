<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use SensitiveParameter;

/**
 * Request body for `POST /v3/payments/{id}/payWithCreditCard`.
 *
 * The OpenAPI spec lists `creditCard` + `creditCardHolderInfo` as required, but
 * Asaas in practice also accepts `creditCardToken` (saved-card flow) in their
 * place. The SDK enforces the cross-field rule: at least one of `creditCardToken`
 * **or** the full (`creditCard`, `creditCardHolderInfo`) pair must be provided.
 * Anything else throws `InvalidArgumentException` at construction time, before
 * the request reaches Asaas.
 */
final readonly class PayWithCreditCardRequest implements JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    public ?CreditCard $creditCard;

    public ?CreditCardHolderInfo $creditCardHolderInfo;

    /**
     * @param  array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}|CreditCard|null  $creditCard
     * @param  array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}|CreditCardHolderInfo|null  $creditCardHolderInfo
     */
    public function __construct(
        #[SensitiveParameter]
        array|CreditCard|null $creditCard = null,
        #[SensitiveParameter]
        array|CreditCardHolderInfo|null $creditCardHolderInfo = null,
        public ?string $remoteIp = null,
        #[SensitiveParameter]
        public ?string $creditCardToken = null,
    ) {
        $this->creditCard = is_array($creditCard) ? CreditCard::fromArray($creditCard) : $creditCard;
        $this->creditCardHolderInfo = is_array($creditCardHolderInfo) ? CreditCardHolderInfo::fromArray($creditCardHolderInfo) : $creditCardHolderInfo;

        if ($creditCardToken === null && (! $this->creditCard instanceof CreditCard || ! $this->creditCardHolderInfo instanceof CreditCardHolderInfo)) {
            throw new InvalidArgumentException(
                'PayWithCreditCardRequest: provide either creditCardToken or both creditCard and creditCardHolderInfo.',
            );
        }
    }

    /** @return array{creditCard: ?array<string, mixed>, creditCardHolderInfo: ?array<string, mixed>, remoteIp: ?string, creditCardToken: ?string} */
    public function __debugInfo(): array
    {
        return [
            'creditCard' => $this->creditCard?->__debugInfo(),
            'creditCardHolderInfo' => $this->creditCardHolderInfo?->__debugInfo(),
            'remoteIp' => $this->remoteIp,
            'creditCardToken' => $this->creditCardToken !== null ? '***' : null,
        ];
    }

    /** @param array{creditCard?: array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}, creditCardHolderInfo?: array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}, remoteIp?: string, creditCardToken?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            creditCard: $data['creditCard'] ?? null,
            creditCardHolderInfo: $data['creditCardHolderInfo'] ?? null,
            remoteIp: $data['remoteIp'] ?? null,
            creditCardToken: $data['creditCardToken'] ?? null,
        );
    }
}
