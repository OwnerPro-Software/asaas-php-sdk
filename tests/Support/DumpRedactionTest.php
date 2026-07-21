<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\RawResponse;
use OwnerPro\Asaas\Support\Redactable;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

mutates(AsaasClient::class);

/**
 * Renders a value exactly the way `dump()` / `dd()` and the Laravel error page
 * do, so these tests exercise the real leak path rather than a stand-in.
 *
 * Nothing is registered here on purpose. `bootstrap/redaction.php` installed the
 * caster when Composer's autoloader was included, which is the only moment early
 * enough to matter — see 'installs the caster before anything can build a
 * cloner'. A helper that registered first would hide a caster that arrives too
 * late, which is exactly the defect this suite once missed.
 */
function dumpToString(mixed $value): string
{
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

it('installs the caster before anything can build a cloner', function (): void {
    // Runs in a fresh process that loads nothing but Composer's autoloader and
    // then builds a cloner immediately — the position Laravel is in, since it
    // constructs the cloner behind dump()/dd() during
    // FoundationServiceProvider::register(), before any boot() runs.
    //
    // A subprocess is the only honest way to pin this: inside the suite the
    // provider has already booted and every entry point has already registered,
    // so nothing left in-process can tell a caster installed in time from one
    // installed too late. Registration moved to Composer's `files` autoload for
    // exactly this reason; drop that entry and this case fails while every
    // other one here still passes.
    $script = <<<'PHP'
        require __DIR__ . '/vendor/autoload.php';
        $cloner = new Symfony\Component\VarDumper\Cloner\VarCloner();
        $card = new OwnerPro\Asaas\Support\DTO\CreditCard('JOHN DOE', '4111111111111111', '12', '2030', '737');
        $stream = fopen('php://memory', 'r+');
        (new Symfony\Component\VarDumper\Dumper\CliDumper($stream))->dump($cloner->cloneVar($card));
        rewind($stream);
        echo stream_get_contents($stream);
        PHP;

    $output = shell_exec(sprintf(
        'cd %s && %s -r %s',
        escapeshellarg(dirname(__DIR__, 2)),
        escapeshellarg(PHP_BINARY),
        escapeshellarg($script),
    ));

    expect($output)
        ->toBeString()
        ->not->toContain('4111111111111111')
        ->not->toContain('737')
        ->toContain('********1111');
});

it('registers exactly one caster, keyed on the interface', function (): void {
    // VarDumper stores one callable per type, so a registration that appended
    // rather than assigned would run the caster once per entry.
    expect(AbstractCloner::$defaultCasters[Redactable::class] ?? null)->toBeInstanceOf(Closure::class);
});

it('answers with the redacted view and discards the collected properties', function (): void {
    $creditCard = new CreditCard('JOHN DOE', '4111111111111111', '12', '2030', '737');

    // Called exactly the way AbstractCloner::castObject() calls it, trailing
    // arguments included, so the narrowed signature is pinned as safe.
    $cast = (AbstractCloner::$defaultCasters[Redactable::class])($creditCard, ['number' => '4111111111111111'], new Stub, false, 0);

    expect($cast)->toBe($creditCard->__debugInfo());
});

it('redacts credentials when a result is json-encoded, which is how it reaches a log', function (): void {
    // `Log::info('created', ['result' => $result])` hands the result to Monolog,
    // which json_encodes its context. Without `jsonSerialize()` the encoder
    // walks the public properties and writes the live key into the log file.
    $result = AsaasResult::success(
        ['id' => 'acc_1', 'apiKey' => '$aact_live_key'],
        new RawResponse(new Response(new GuzzleResponse(200, [], '{"id":"acc_1"}'))),
    );

    $encoded = json_encode($result);

    expect($encoded)->toContain('"apiKey":"***"')
        ->and($encoded)->not->toContain('$aact_live_key');
});

it('redacts a credential on every row when a page is json-encoded', function (): void {
    $page = AsaasPaginatedResult::success(
        [['id' => 'wh_1', 'authToken' => 'secret-one'], ['id' => 'wh_2', 'authToken' => 'secret-two']],
        totalCount: 2,
        hasMore: false,
        limit: 10,
        offset: 0,
        rawResponse: new RawResponse(new Response(new GuzzleResponse(200, [], '{}'))),
        nextPageFetcher: null,
    );

    $encoded = json_encode($page);

    expect($encoded)->not->toContain('secret-one')
        ->and($encoded)->not->toContain('secret-two')
        ->and($encoded)->toContain('"authToken":"***"');
});

it('keeps the real value reachable on the property after json redaction', function (): void {
    // Redaction is a display concern: the caller still has to store the key
    // Asaas shows exactly once.
    $result = AsaasResult::success(
        ['apiKey' => '$aact_live_key'],
        new RawResponse(new Response(new GuzzleResponse(200, [], '{}'))),
    );

    expect($result->data['apiKey'])->toBe('$aact_live_key');
});
