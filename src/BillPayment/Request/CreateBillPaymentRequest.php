<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreateBillPaymentRequest
{
    use HasArrayFactory;

    public function __construct(
        public string $identificationField,
        public ?string $scheduleDate = null,
        public ?string $description = null,
        public ?float $discount = null,
        public ?float $interest = null,
        public ?float $fine = null,
        public ?string $dueDate = null,
        public ?string $externalReference = null,
    ) {}

    /** @param array{identificationField?: string, scheduleDate?: string, description?: string, discount?: float, interest?: float, fine?: float, dueDate?: string, externalReference?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            identificationField: $data['identificationField'] ?? throw new InvalidArgumentException('identificationField is required'),
            scheduleDate: $data['scheduleDate'] ?? null,
            description: $data['description'] ?? null,
            discount: $data['discount'] ?? null,
            interest: $data['interest'] ?? null,
            fine: $data['fine'] ?? null,
            dueDate: $data['dueDate'] ?? null,
            externalReference: $data['externalReference'] ?? null,
        );
    }
}
