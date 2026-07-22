<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Support\RawResponse;
use OwnerPro\Asaas\Support\Redactable;
use OwnerPro\Asaas\Support\RequestNotDeliveredException;
use OwnerPro\Asaas\Support\TransportException;
use OwnerPro\Asaas\Support\TransportFailureClassifier;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

mutates(
    AsaasClient::class,
    TransportException::class,
    RequestNotDeliveredException::class,
    IndeterminateResultException::class,
);

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

it('redacts the error object a walk yields in place of a page', function (): void {
    // This is what `all()` hands the caller when a page is rejected, so it
    // reaches a log by the same two routes the results do.
    $error = AsaasPaginatedError::fromApi(
        [['code' => 'invalid_value', 'description' => 'rejected', 'apiKey' => '$aact_live_key']],
        new RawResponse(new Response(new GuzzleResponse(400, [], '{}'))),
        offset: 10,
        limit: 10,
    );

    expect(json_encode($error))->not->toContain('$aact_live_key')
        ->and(dumpToString($error))->not->toContain('$aact_live_key')
        ->and($error->errors[0]['apiKey'])->toBe('$aact_live_key');

    // The redacted view is still the whole object: an error that lost its
    // offset, limit or response tells the reader nothing about which page
    // failed, which is the only reason to look at one.
    $decoded = json_decode((string) json_encode($error), true);

    expect($decoded)->toBe([
        'errors' => [['code' => 'invalid_value', 'description' => 'rejected', 'apiKey' => '***']],
        'response' => ['status' => 400, 'headers' => [], 'body' => '[]'],
        'offset' => 10,
        'limit' => 10,
    ]);
});

it('keeps the api key out of a var_exported result', function (): void {
    // `var_export()` is the one reader that asks an object nothing: it ignores
    // __debugInfo() and jsonSerialize() and walks private properties directly.
    // A result is exactly what an app exports when a payment is rejected, and
    // the response it carries used to hold the Illuminate response — and
    // through its transfer stats, the *request* headers. Nothing but not
    // holding it can close that path.
    Http::fake(['*' => Http::response(['errors' => [['code' => 'invalid_value', 'description' => 'rejected']]], 400)]);

    $result = (new PaymentResource(AsaasConnector::forLaravel('$aact_live_key', Environment::Sandbox, 30)))->find('pay_1');

    expect(var_export($result, true))->not->toContain('$aact_live_key')
        ->and(var_export($result->response, true))->not->toContain('$aact_live_key');
});

it('redacts a credential on the exception orFail() throws', function (): void {
    // `orFail()` hands the same rows to an exception, and an exception reaches a
    // log by more routes than a result does — `Log::error('failed', ['e' => $e])`
    // and the framework error page both render it. Redacting the result while
    // its own thrown form printed the key left the credential one `->orFail()`
    // away from the log line.
    $result = AsaasResult::failure(
        [['code' => 'invalid_cpfCnpj', 'description' => 'rejected', 'apiKey' => '$aact_live_key']],
        new RawResponse(new Response(new GuzzleResponse(400, [], '{}'))),
    );

    $exception = null;

    try {
        $result->orFail();
    } catch (AsaasRequestException $asaasRequestException) {
        $exception = $asaasRequestException;
    }

    expect($exception)->toBeInstanceOf(AsaasRequestException::class)
        ->and(json_encode($exception))->not->toContain('$aact_live_key')
        ->and(dumpToString($exception))->not->toContain('$aact_live_key')
        // Redaction is a display concern here too: the caller still has to be
        // able to read what Asaas actually rejected.
        ->and($exception->errors[0]['apiKey'])->toBe('$aact_live_key');

    $decoded = json_decode((string) json_encode($exception), true);

    expect($decoded['errors'])->toBe([['code' => 'invalid_cpfCnpj', 'description' => 'rejected', 'apiKey' => '***']])
        ->and($decoded['statusCode'])->toBe(400)
        ->and($decoded['message'])->toBe('rejected')
        ->and($decoded['response'])->toBe(['status' => 400, 'headers' => [], 'body' => '[]']);
});

it('keeps the exception readable as an exception once the caster replaces its properties', function (): void {
    // The caster replaces the property list outright, so a redacted view that
    // dropped the message, file and line would trade a leak for an exception
    // nobody can debug.
    $exception = new AsaasRequestException([['code' => 'invalid_value', 'description' => 'rejected']], null);

    $output = dumpToString($exception);
    $decoded = json_decode((string) json_encode($exception), true);

    expect($output)->toContain('rejected')
        ->and($output)->toContain('statusCode')
        ->and($output)->toContain(basename(__FILE__))
        // File without line points at a 200-line method, which is the same as
        // pointing nowhere.
        ->and($decoded['file'])->toBe($exception->getFile())
        ->and($decoded['line'])->toBe($exception->getLine());
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

// --- transport failures ---

/**
 * The chain Laravel's `PendingRequest::marshalConnectionException()` really
 * builds: the Guzzle exception keeps the PSR-7 request it failed on, and that
 * request carries the `access_token` header. Nothing on the SDK's own
 * exception is a secret — the key is two hops down `getPrevious()`.
 *
 * @param  array<string, mixed>  $context
 */
function transportFailureCarryingKey(string $apiKey, array $context): ConnectionException
{
    $curlFailure = new ConnectException(
        'cURL failure',
        new Psr7Request('POST', 'https://api-sandbox.asaas.com/v3/payments', ['access_token' => $apiKey]),
        null,
        $context,
    );

    return new ConnectionException($curlFailure->getMessage(), 0, $curlFailure);
}

it('keeps the API key out of a RequestNotDeliveredException on every view that reads __debugInfo', function (): void {
    // README tells callers to catch this exception, so a caller holding it and
    // reaching for dd($e) is the documented path, not a stray one.
    $exception = TransportFailureClassifier::classify(transportFailureCarryingKey('$aact_live_key', ['errno' => 7]));

    expect($exception)->toBeInstanceOf(RequestNotDeliveredException::class)
        ->and(dumpToString($exception))->not->toContain('$aact_live_key')
        ->and(print_r($exception, true))->not->toContain('$aact_live_key')
        ->and(json_encode($exception))->not->toContain('$aact_live_key');
});

it('keeps the API key out of an IndeterminateResultException, response included', function (): void {
    $exception = TransportFailureClassifier::classify(transportFailureCarryingKey('$aact_live_key', ['errno' => 28]));

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class)
        ->and(dumpToString($exception))->not->toContain('$aact_live_key')
        ->and(print_r($exception, true))->not->toContain('$aact_live_key')
        ->and(json_encode($exception))->not->toContain('$aact_live_key');
});

it('redacts the attached response of an indeterminate result without hiding it', function (): void {
    $exception = new IndeterminateResultException(
        'server',
        null,
        new RawResponse(new Response(new GuzzleResponse(502, [], '{"apiKey":"$aact_live_key"}'))),
    );

    $decoded = json_decode((string) json_encode($exception), true);

    expect($decoded['response']['status'])->toBe(502)
        ->and($decoded['response']['body'])->not->toContain('$aact_live_key')
        ->and($decoded['phase'])->toBe('server')
        // The replaced view still has to read as an exception report, or
        // redaction costs the reader the reason they dumped it.
        ->and($decoded['message'])->toBe($exception->getMessage())
        ->and($decoded['file'])->toBe($exception->getFile())
        ->and($decoded['line'])->toBe($exception->getLine());
});

it('keeps the diagnostic chain reachable behind the redacted view', function (): void {
    // The view is replaced, not the object: the errno behind the verdict is
    // only readable through getPrevious(), which is why emptying the exception
    // was not an option.
    $connectionException = transportFailureCarryingKey('$aact_live_key', ['errno' => 7]);

    $exception = TransportFailureClassifier::classify($connectionException);

    expect($exception->getPrevious())->toBe($connectionException);
});

it('keeps a transport failure readable as an exception once the caster replaces its properties', function (): void {
    $exception = TransportFailureClassifier::classify(transportFailureCarryingKey('$aact_live_key', ['errno' => 6]));

    $output = dumpToString($exception);
    $decoded = json_decode((string) json_encode($exception), true);

    expect($output)->toContain('dns')
        ->and($output)->toContain('safe to retry')
        ->and($decoded['message'])->toBe($exception->getMessage())
        ->and($decoded['phase'])->toBe('dns')
        ->and($decoded['file'])->toBe($exception->getFile())
        ->and($decoded['line'])->toBe($exception->getLine());
});
