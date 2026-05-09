<?php

declare(strict_types=1);

use OwnerPro\Asaas\Testing\NoMatchingStubException;

mutates(NoMatchingStubException::class);

it('formats message with method, url and registered patterns', function (): void {
    $noMatchingStubException = NoMatchingStubException::for(
        method: 'POST',
        url: 'https://sandbox.asaas.com/api/v3/payments',
        registered: ['payments/*', 'webhooks'],
    );

    expect($noMatchingStubException->getMessage())->toBe(
        "No stub matched POST https://sandbox.asaas.com/api/v3/payments\n\n"
        ."Registered stubs:\n"
        ."  - payments/*\n"
        ."  - webhooks\n\n"
        ."Hint: register a stub via AsaasClient::fake([...]) or ->stub('pattern', ...). "
        .'Other helpers: ->stubError(), ->stubException(), ->stubPages().',
    );
});

it('formats message with empty registered list', function (): void {
    $noMatchingStubException = NoMatchingStubException::for(
        method: 'GET',
        url: 'https://sandbox.asaas.com/api/v3/payments/pay_1',
        registered: [],
    );

    expect($noMatchingStubException->getMessage())->toBe(
        "No stub matched GET https://sandbox.asaas.com/api/v3/payments/pay_1\n\n"
        ."Registered stubs:\n"
        ."  (none)\n\n"
        ."Hint: register a stub via AsaasClient::fake([...]) or ->stub('pattern', ...). "
        .'Other helpers: ->stubError(), ->stubException(), ->stubPages().',
    );
});

it('returns a RuntimeException', function (): void {
    $noMatchingStubException = NoMatchingStubException::for(
        method: 'GET',
        url: 'https://example.test',
        registered: [],
    );

    expect($noMatchingStubException)->toBeInstanceOf(RuntimeException::class);
});
