<?php

declare(strict_types=1);

use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DumpRedaction;
use OwnerPro\Asaas\Support\RawResponse;
use OwnerPro\Asaas\Support\Redactable;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

mutates(AsaasClient::class, DumpRedaction::class);

/**
 * Renders a value exactly the way `dump()` / `dd()` and the Laravel error page
 * do, so these tests exercise the real leak path rather than a stand-in.
 *
 * The cloner is built *after* registration on purpose: `AbstractCloner` copies
 * `$defaultCasters` in its constructor, so a cloner made earlier would never
 * see the caster.
 */
function dumpToString(mixed $value): string
{
    DumpRedaction::register();

    $stream = fopen('php://memory', 'r+');
    expect($stream)->not->toBeFalse();

    (new CliDumper($stream))->dump((new VarCloner)->cloneVar($value));

    rewind($stream);
    $output = stream_get_contents($stream);
    fclose($stream);

    return (string) $output;
}

it('keeps the api key out of a dumped client', function (): void {
    $output = dumpToString(AsaasClient::for(apiKey: 'SUPER_SECRET_KEY_123'));

    expect($output)
        ->not->toContain('SUPER_SECRET_KEY_123')
        ->not->toContain('access_token')
        ->toContain('payments');
});

it('keeps the pan and cvv out of a dumped credit card', function (): void {
    $output = dumpToString(new CreditCard('JOHN DOE', '4111111111111111', '12', '2030', '737'));

    expect($output)
        ->not->toContain('4111111111111111')
        ->not->toContain('737')
        ->toContain('********1111')
        ->toContain('JOHN DOE');
});

it('keeps the account and document out of a dumped bank account', function (): void {
    $output = dumpToString(new BankAccount(
        bank: ['code' => '001'],
        accountName: 'Main',
        ownerName: 'JOHN DOE',
        cpfCnpj: '24971563792',
        agency: '1234',
        account: '9876543',
        accountDigit: '1',
    ));

    expect($output)
        ->not->toContain('24971563792')
        ->not->toContain('9876543')
        ->toContain('JOHN DOE');
});

it('keeps the document and contact details out of a dumped card holder', function (): void {
    $output = dumpToString(new CreditCardHolderInfo(
        name: 'JOHN DOE',
        email: 'john@example.com',
        cpfCnpj: '24971563792',
        postalCode: '01310000',
        addressNumber: '100',
        phone: '1140028922',
    ));

    expect($output)
        ->not->toContain('24971563792')
        ->not->toContain('john@example.com')
        ->not->toContain('1140028922')
        ->toContain('JOHN DOE');
});

it('does not expose the illuminate response behind a dumped raw response', function (): void {
    $output = dumpToString(RawResponse::fake(201, ['X-Trace' => 'abc'], '{"id":"pay_1"}'));

    expect($output)
        ->toContain('pay_1')
        ->not->toContain('Illuminate\Http\Client\Response');
});

it('redacts a redactable nested deep inside another structure', function (): void {
    $output = dumpToString(['payload' => ['card' => new CreditCard('JOHN DOE', '4111111111111111', '12', '2030', '737')]]);

    expect($output)->not->toContain('4111111111111111');
});

it('installs the caster from the standalone entry point', function (): void {
    // Deliberately does not call register() itself: under Testbench the service
    // provider has already booted, so only starting from an uninstalled caster
    // proves `AsaasClient::for()` covers hosts that have no provider to boot.
    unset(AbstractCloner::$defaultCasters[Redactable::class]);

    AsaasClient::for(apiKey: 'key');

    expect(AbstractCloner::$defaultCasters[Redactable::class] ?? null)->toBeInstanceOf(Closure::class);
});

it('registers one caster no matter how many entry points run', function (): void {
    DumpRedaction::register();
    DumpRedaction::register();
    AsaasClient::for(apiKey: 'key');

    // A plain assignment, so repeated entry points overwrite rather than stack.
    // VarDumper stores one callable per type; an array of callables here would
    // mean the caster runs once per registration.
    expect(AbstractCloner::$defaultCasters[Redactable::class])->toBeInstanceOf(Closure::class);
});

it('answers with the redacted view and discards the collected properties', function (): void {
    DumpRedaction::register();
    $creditCard = new CreditCard('JOHN DOE', '4111111111111111', '12', '2030', '737');

    // Called exactly the way AbstractCloner::castObject() calls it, trailing
    // arguments included, so the narrowed signature is pinned as safe.
    $cast = (AbstractCloner::$defaultCasters[Redactable::class])($creditCard, ['number' => '4111111111111111'], new Stub, false, 0);

    expect($cast)->toBe($creditCard->__debugInfo());
});
