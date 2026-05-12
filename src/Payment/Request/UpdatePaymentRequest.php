<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\DiscountType;
use OwnerPro\Asaas\Payment\FineType;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\Discount;
use OwnerPro\Asaas\Support\DTO\Fine;
use OwnerPro\Asaas\Support\DTO\Interest;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\Missing;

/**
 * Partial-update DTO for `PUT /v3/payments/{id}`. The OpenAPI spec marks
 * `billingType`, `value` and `dueDate` as required (REST full-replace), but
 * the Asaas API accepts partial bodies in practice — the canonical use case
 * is changing a single field (e.g. only `dueDate`). Every field defaults to
 * `Missing::Value` so omitted keys never reach the wire.
 *
 * Caller responsibility: when re-issuing the payment from scratch, include the
 * spec-required trio.
 */
final readonly class UpdatePaymentRequest
{
    use HasUpdatableArrayFactory;

    /** @var list<Split>|Missing|null */
    public array|Missing|null $split;

    public Callback|Missing|null $callback;

    public Discount|Missing|null $discount;

    public Interest|Missing|null $interest;

    public Fine|Missing|null $fine;

    /**
     * @param  list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}|Split>|Missing|null  $split
     * @param  array{successUrl?: string, autoRedirect?: bool}|Callback|Missing|null  $callback
     * @param  array{value?: float, dueDateLimitDays?: int, type?: DiscountType|string}|Discount|float|Missing|null  $discount
     * @param  array{value?: float}|Interest|float|Missing|null  $interest
     * @param  array{value?: float, type?: FineType|string}|Fine|float|Missing|null  $fine
     */
    public function __construct(
        public BillingType|string|Missing|null $billingType = Missing::Value,
        public float|Missing|null $value = Missing::Value,
        public string|Missing|null $dueDate = Missing::Value,
        public string|Missing|null $description = Missing::Value,
        public string|Missing|null $externalReference = Missing::Value,
        array|Discount|float|Missing|null $discount = Missing::Value,
        array|Interest|float|Missing|null $interest = Missing::Value,
        array|Fine|float|Missing|null $fine = Missing::Value,
        public bool|Missing|null $postalService = Missing::Value,
        public int|Missing|null $daysAfterDueDateToRegistrationCancellation = Missing::Value,
        array|Missing|null $split = Missing::Value,
        array|Callback|Missing|null $callback = Missing::Value,
    ) {
        $this->split = is_array($split) ? array_map(
            fn (array|Split $item): Split => $item instanceof Split ? $item : Split::fromArray($item),
            $split,
        ) : $split;
        $this->callback = Callback::coerce($callback);
        $this->discount = Discount::coerce($discount);
        $this->interest = Interest::coerce($interest);
        $this->fine = Fine::coerce($fine);
    }

    /** @param array{billingType?: BillingType|string|null, value?: float|null, dueDate?: string|null, description?: string|null, externalReference?: string|null, discount?: array{value?: float, dueDateLimitDays?: int, type?: DiscountType|string}|Discount|float|null, interest?: array{value?: float}|Interest|float|null, fine?: array{value?: float, type?: FineType|string}|Fine|float|null, postalService?: bool|null, daysAfterDueDateToRegistrationCancellation?: int|null, split?: list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}>|null, callback?: array{successUrl?: string, autoRedirect?: bool}|null} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            billingType: array_key_exists('billingType', $data) ? $data['billingType'] : Missing::Value,
            value: array_key_exists('value', $data) ? $data['value'] : Missing::Value,
            dueDate: array_key_exists('dueDate', $data) ? $data['dueDate'] : Missing::Value,
            description: array_key_exists('description', $data) ? $data['description'] : Missing::Value,
            externalReference: array_key_exists('externalReference', $data) ? $data['externalReference'] : Missing::Value,
            discount: array_key_exists('discount', $data) ? $data['discount'] : Missing::Value,
            interest: array_key_exists('interest', $data) ? $data['interest'] : Missing::Value,
            fine: array_key_exists('fine', $data) ? $data['fine'] : Missing::Value,
            postalService: array_key_exists('postalService', $data) ? $data['postalService'] : Missing::Value,
            daysAfterDueDateToRegistrationCancellation: array_key_exists('daysAfterDueDateToRegistrationCancellation', $data) ? $data['daysAfterDueDateToRegistrationCancellation'] : Missing::Value,
            split: array_key_exists('split', $data) ? $data['split'] : Missing::Value,
            callback: array_key_exists('callback', $data) ? $data['callback'] : Missing::Value,
        );
    }
}
