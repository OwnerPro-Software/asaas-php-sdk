<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\Request\EscrowConfigRequest;

mutates(EscrowConfigRequest::class);

it('builds from array with all fields', function (): void {
    $req = EscrowConfigRequest::fromArray([
        'daysToExpire' => 30,
        'enabled' => true,
        'isFeePayer' => false,
    ]);

    expect($req->toArray())->toBe([
        'daysToExpire' => 30,
        'enabled' => true,
        'isFeePayer' => false,
    ]);
});

it('omits Missing optional fields from toArray', function (): void {
    $req = new EscrowConfigRequest(daysToExpire: 15);

    expect($req->toArray())->toBe(['daysToExpire' => 15]);
});
