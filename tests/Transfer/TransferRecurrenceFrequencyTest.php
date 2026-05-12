<?php

declare(strict_types=1);

use OwnerPro\Asaas\Transfer\TransferRecurrenceFrequency;

mutates(TransferRecurrenceFrequency::class);

it('exposes the documented recurrence frequency cases', function (): void {
    expect(array_map(fn (TransferRecurrenceFrequency $c): string => $c->value, TransferRecurrenceFrequency::cases()))->toBe([
        'WEEKLY', 'MONTHLY',
    ]);
});
