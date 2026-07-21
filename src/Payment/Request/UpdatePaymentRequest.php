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

    /** @var list<Split>|Missing */
    public array|Missing $split;

    public Callback|Missing $callback;

    public Discount|Missing $discount;

    public Interest|Missing $interest;

    public Fine|Missing $fine;

    /**
     * @param  list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}|Split>|Missing  $split
     * @param  array{successUrl?: string, autoRedirect?: bool}|Callback|Missing  $callback
     * @param  array{value?: float, dueDateLimitDays?: int, type?: DiscountType|string}|Discount|float|Missing  $discount
     * @param  array{value?: float}|Interest|float|Missing  $interest
     * @param  array{value?: float, type?: FineType|string}|Fine|float|Missing  $fine
     * @param  string|Missing  $dueDate  Format `YYYY-MM-DD`.
     */
    public function __construct(
        public BillingType|string|Missing $billingType = Missing::Value,
        public float|Missing $value = Missing::Value,
        public string|Missing $dueDate = Missing::Value,
        public string|Missing $description = Missing::Value,
        public string|Missing $externalReference = Missing::Value,
        array|Discount|float|Missing $discount = Missing::Value,
        array|Interest|float|Missing $interest = Missing::Value,
        array|Fine|float|Missing $fine = Missing::Value,
        public bool|Missing $postalService = Missing::Value,
        public int|Missing $daysAfterDueDateToRegistrationCancellation = Missing::Value,
        array|Missing $split = Missing::Value,
        array|Callback|Missing $callback = Missing::Value,
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

    /** @param array{billingType?: BillingType|string, value?: float, dueDate?: string, description?: string, externalReference?: string, discount?: array{value?: float, dueDateLimitDays?: int, type?: DiscountType|string}|Discount|float, interest?: array{value?: float}|Interest|float, fine?: array{value?: float, type?: FineType|string}|Fine|float, postalService?: bool, daysAfterDueDateToRegistrationCancellation?: int, split?: list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}>, callback?: array{successUrl?: string, autoRedirect?: bool}} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            billingType: $data['billingType'] ?? Missing::Value,
            value: $data['value'] ?? Missing::Value,
            dueDate: $data['dueDate'] ?? Missing::Value,
            description: $data['description'] ?? Missing::Value,
            externalReference: $data['externalReference'] ?? Missing::Value,
            discount: $data['discount'] ?? Missing::Value,
            interest: $data['interest'] ?? Missing::Value,
            fine: $data['fine'] ?? Missing::Value,
            postalService: $data['postalService'] ?? Missing::Value,
            daysAfterDueDateToRegistrationCancellation: $data['daysAfterDueDateToRegistrationCancellation'] ?? Missing::Value,
            split: $data['split'] ?? Missing::Value,
            callback: $data['callback'] ?? Missing::Value,
        );
    }
}
