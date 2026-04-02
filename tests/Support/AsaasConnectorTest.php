<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\BaseResponse;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\Environment;

mutates(AsaasConnector::class);

final class ConnectorTestResponse extends BaseResponse
{
    public string $id;

    public ?string $status;
}

it('implements Connector interface', function (): void {
    expect(AsaasConnector::class)->toImplement(Connector::class);
});

// --- forLaravel factory ---

it('forLaravel accepts string environment', function (): void {
    Http::fake(['https://api.asaas.com/*' => Http::response(['id' => 'x', 'status' => 'OK'], 200)]);

    $connector = AsaasConnector::forLaravel('key', 'production', 30);
    $result = $connector->get('/v3/payments/x', [], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.asaas.com/'));
});

it('forLaravel throws on invalid environment string', function (): void {
    AsaasConnector::forLaravel('key', 'invalid', 30);
})->throws(ValueError::class);

it('forLaravel uses sandbox base url', function (): void {
    Http::fake(['https://api-sandbox.asaas.com/*' => Http::response(['id' => 'x', 'status' => 'OK'], 200)]);

    $connector = AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
    $result = $connector->get('/v3/payments/x', [], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('x');

    Http::assertSent(function ($request): bool {
        return $request->hasHeader('access_token', 'test-key')
            && str_starts_with($request->url(), 'https://api-sandbox.asaas.com/');
    });
});

it('forLaravel uses production base url', function (): void {
    Http::fake(['https://api.asaas.com/*' => Http::response(['id' => 'x', 'status' => 'OK'], 200)]);

    $connector = AsaasConnector::forLaravel('prod-key', Environment::Production, 30);
    $result = $connector->get('/v3/payments/x', [], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        return str_starts_with($request->url(), 'https://api.asaas.com/');
    });
});

// --- forStandalone factory ---

it('forStandalone accepts string environment', function (): void {
    $connector = AsaasConnector::forStandalone('key', 'sandbox', 30);

    expect($connector)->toBeInstanceOf(AsaasConnector::class);
});

it('forStandalone throws on invalid environment string', function (): void {
    AsaasConnector::forStandalone('key', 'invalid', 30);
})->throws(ValueError::class);

it('forStandalone creates connector for sandbox', function (): void {
    $connector = AsaasConnector::forStandalone('test-key', Environment::Sandbox, 30);

    expect($connector)->toBeInstanceOf(AsaasConnector::class);
});

it('forStandalone creates connector for production', function (): void {
    $connector = AsaasConnector::forStandalone('test-key', Environment::Production, 30);

    expect($connector)->toBeInstanceOf(AsaasConnector::class);
});

// --- Standalone HTTP behavior via DI constructor with stubbed PendingRequest ---

it('standalone get returns success result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['id' => 'pay_123', 'status' => 'PENDING'], 200)]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->get('/v3/payments/pay_123', [], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('pay_123');
    expect($result->statusCode)->toBe(200);
});

it('standalone post returns success result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['id' => 'pay_new', 'status' => 'PENDING'], 200)]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->post('/v3/payments', ['value' => 100], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('pay_new');
});

it('standalone put returns success result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['id' => 'pay_123', 'status' => 'UPDATED'], 200)]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->put('/v3/payments/pay_123', ['value' => 200], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    expect($result->data->status)->toBe('UPDATED');
});

it('standalone delete returns success result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['deleted' => true, 'id' => 'pay_123'], 200)]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->delete('/v3/payments/pay_123', ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
});

it('standalone returns failure result on error response', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(
            json_decode(file_get_contents(__DIR__.'/../Fixtures/error_400.json'), true),
            400,
        )]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->post('/v3/payments', ['bad' => 'data'], ConnectorTestResponse::class);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
    expect($result->data)->toBeNull();
});

it('standalone paginate returns paginated result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(
            json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true),
            200,
        )]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->paginate('/v3/payments', ['limit' => 10], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0]->id)->toBe('pay_1');
    expect($result->totalCount)->toBe(50);
    expect($result->hasMore)->toBeTrue();
});

it('standalone returns empty errors when error response has no errors array', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response('Internal Server Error', 500)]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->post('/v3/payments', ['bad' => 'data'], ConnectorTestResponse::class);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(500);
    expect($result->errors)->toBe([]);
    expect($result->data)->toBeNull();
});

it('standalone get returns failure result on connection exception', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->get('/v3/payments/pay_123', [], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(0);
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'cURL error 28: Connection timed out']]);
    expect($result->data)->toBeNull();
});

it('standalone paginate returns failure result on connection exception', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options): never => throw new ConnectionException('cURL error 7: Failed to connect')]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->paginate('/v3/payments', [], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(0);
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'cURL error 7: Failed to connect']]);
    expect($result->data)->toBe([]);
});

// --- Laravel HTTP behavior (existing tests adapted to forLaravel) ---

it('returns AsaasResult with response on successful GET', function (): void {
    Http::fake(['*' => Http::response(
        json_decode(file_get_contents(__DIR__.'/../Fixtures/payment.json'), true),
        200
    )]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->get('/v3/payments/pay_123', [], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('pay_123');
    expect($result->statusCode)->toBe(200);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_123'
        && $request->method() === 'GET');
});

it('returns AsaasResult with response on successful POST', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_new', 'status' => 'PENDING'], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->post('/v3/payments', ['value' => 100], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('pay_new');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
        && $request->method() === 'POST');
});

it('returns AsaasResult with response on successful PUT', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_123', 'status' => 'UPDATED'], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->put('/v3/payments/pay_123', ['value' => 200], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    expect($result->data->status)->toBe('UPDATED');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_123'
        && $request->method() === 'PUT');
});

it('returns AsaasResult on DELETE', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true, 'id' => 'pay_123'], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->delete('/v3/payments/pay_123', ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_123'
        && $request->method() === 'DELETE');
});

it('returns failure result on error response', function (): void {
    Http::fake(['*' => Http::response(
        json_decode(file_get_contents(__DIR__.'/../Fixtures/error_400.json'), true),
        400
    )]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->post('/v3/payments', ['bad' => 'data'], ConnectorTestResponse::class);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
    expect($result->data)->toBeNull();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
        && $request->method() === 'POST');
});

it('returns AsaasPaginatedResult on paginate', function (): void {
    Http::fake(['*' => Http::response(
        json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true),
        200
    )]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/v3/payments', ['limit' => 10], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0]->id)->toBe('pay_1');
    expect($result->totalCount)->toBe(50);
    expect($result->hasMore)->toBeTrue();
    expect($result->limit)->toBe(10);
    expect($result->offset)->toBe(0);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/payments'));
});

it('paginate returns failure on error', function (): void {
    Http::fake(['*' => Http::response(
        json_decode(file_get_contents(__DIR__.'/../Fixtures/error_400.json'), true),
        400
    )]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/v3/payments', [], ConnectorTestResponse::class);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(400);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/payments'));
});

it('iterates all pages lazily via all()', function (): void {
    $page1 = json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true);
    $page2 = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 50, 'limit' => 10, 'offset' => 10,
        'data' => [['id' => 'pay_3', 'status' => 'PAID']],
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/v3/payments', ['limit' => 10], ConnectorTestResponse::class));

    expect($items)->toHaveCount(3);
    expect($items[0]->id)->toBe('pay_1');
    expect($items[2]->id)->toBe('pay_3');

    Http::assertSentCount(2);
});

it('all() uses default limit of 100 when not specified', function (): void {
    $page = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 1, 'limit' => 100, 'offset' => 0,
        'data' => [['id' => 'pay_1', 'status' => 'DONE']],
    ];

    Http::fake(['*' => Http::response($page, 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/v3/payments', [], ConnectorTestResponse::class));

    expect($items)->toHaveCount(1);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'limit=100')
        && str_contains($request->url(), 'offset=0'));
});

it('all() sends correct offset and limit on each page', function (): void {
    $page1 = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 2, 'offset' => 0,
        'data' => [['id' => 'a1', 'status' => 'OK'], ['id' => 'a2', 'status' => 'OK']],
    ];
    $page2 = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 3, 'limit' => 2, 'offset' => 2,
        'data' => [['id' => 'a3', 'status' => 'OK']],
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/v3/payments', ['limit' => 2], ConnectorTestResponse::class));

    expect($items)->toHaveCount(3);
    expect($items[0]->id)->toBe('a1');
    expect($items[2]->id)->toBe('a3');

    /** @var list<array{0: Request, 1: Response}> $recorded */
    $recorded = Http::recorded();
    expect($recorded)->toHaveCount(2);

    $firstUrl = $recorded[0][0]->url();
    $secondUrl = $recorded[1][0]->url();

    expect($firstUrl)->toContain('offset=0');
    expect($firstUrl)->toContain('limit=2');
    expect($secondUrl)->toContain('offset=2');
    expect($secondUrl)->toContain('limit=2');
});

it('all() enforces minimum limit of 1', function (): void {
    $page = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 1, 'limit' => 1, 'offset' => 0,
        'data' => [['id' => 'pay_1', 'status' => 'DONE']],
    ];

    Http::fake(['*' => Http::response($page, 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/v3/payments', ['limit' => 0], ConnectorTestResponse::class));

    expect($items)->toHaveCount(1);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'limit=1'));
});

it('all() enforces minimum limit of 1 for negative values', function (): void {
    $page = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 1, 'limit' => 1, 'offset' => 0,
        'data' => [['id' => 'pay_1', 'status' => 'DONE']],
    ];

    Http::fake(['*' => Http::response($page, 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/v3/payments', ['limit' => -5], ConnectorTestResponse::class));

    expect($items)->toHaveCount(1);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'limit=1'));
});

it('all() stops when API returns hasMore true with empty data', function (): void {
    $emptyPage = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 10, 'limit' => 10, 'offset' => 0,
        'data' => [],
    ];

    Http::fake(['*' => Http::response($emptyPage, 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/v3/payments', [], ConnectorTestResponse::class));

    expect($items)->toBeEmpty();

    Http::assertSentCount(1);
});

it('all() throws on error during pagination', function (): void {
    $page1 = json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true);
    $errorResponse = json_decode(file_get_contents(__DIR__.'/../Fixtures/error_400.json'), true);

    Http::fakeSequence()->push($page1, 200)->push($errorResponse, 400);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    iterator_to_array($connector->all('/v3/payments', ['limit' => 10], ConnectorTestResponse::class));
})->throws(AsaasRequestException::class, 'The value field is required');

it('paginate uses defaults for missing pagination fields', function (): void {
    Http::fake(['*' => Http::response([
        'data' => [['id' => 'x', 'status' => 'OK']],
    ], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/v3/payments', [], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    expect($result->totalCount)->toBe(0);
    expect($result->hasMore)->toBeFalse();
    expect($result->limit)->toBe(0);
    expect($result->offset)->toBe(0);
    expect($result->data)->toHaveCount(1);
});

it('returns empty errors when error response has no errors array', function (): void {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->post('/v3/payments', ['bad' => 'data'], ConnectorTestResponse::class);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(500);
    expect($result->errors)->toBe([]);
    expect($result->data)->toBeNull();
});

it('paginate returns empty errors when error response has no errors array', function (): void {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/v3/payments', [], ConnectorTestResponse::class);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(500);
    expect($result->errors)->toBe([]);
});

it('paginate next page fetcher passes correct offset in URL', function (): void {
    $page1 = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 2, 'offset' => 0,
        'data' => [['id' => 'x1', 'status' => 'OK'], ['id' => 'x2', 'status' => 'OK']],
    ];
    $page2 = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 3, 'limit' => 2, 'offset' => 2,
        'data' => [['id' => 'x3', 'status' => 'OK']],
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/v3/payments', ['limit' => 2], ConnectorTestResponse::class);

    expect($result->hasMore)->toBeTrue();

    $next = $result->next();

    expect($next)->not->toBeNull();
    expect($next->data)->toHaveCount(1);
    expect($next->data[0]->id)->toBe('x3');
    expect($next->offset)->toBe(2);
    expect($next->hasMore)->toBeFalse();

    /** @var list<array{0: Request, 1: Response}> $recorded */
    $recorded = Http::recorded();
    expect($recorded)->toHaveCount(2);

    $secondUrl = $recorded[1][0]->url();
    expect($secondUrl)->toContain('offset=2');
});

it('returns success with empty response on 2xx with no JSON body', function (): void {
    Http::fake(['*' => Http::response('', 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->get('/v3/payments/x', [], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    expect($result->statusCode)->toBe(200);
});

it('standalone handles 2xx with no JSON body', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response('', 200)]);

    $connector = new AsaasConnector($pendingRequest);
    $result = $connector->get('/v3/payments/x', [], ConnectorTestResponse::class);

    expect($result->success)->toBeTrue();
    expect($result->statusCode)->toBe(200);
});

// --- ConnectionException handling (Laravel mode) ---

it('get returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->get('/v3/payments/pay_123', [], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(0);
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'cURL error 28: Connection timed out']]);
    expect($result->data)->toBeNull();
});

it('post returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('Connection refused')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->post('/v3/payments', ['value' => 100], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(0);
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Connection refused']]);
    expect($result->data)->toBeNull();
});

it('put returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('DNS resolution failed')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->put('/v3/payments/pay_123', ['value' => 200], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(0);
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'DNS resolution failed']]);
    expect($result->data)->toBeNull();
});

it('delete returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('Connection reset by peer')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->delete('/v3/payments/pay_123', ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(0);
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Connection reset by peer']]);
    expect($result->data)->toBeNull();
});

it('paginate returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('cURL error 7: Failed to connect')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/v3/payments', [], ConnectorTestResponse::class);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(0);
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'cURL error 7: Failed to connect']]);
    expect($result->data)->toBe([]);
});

it('all() throws AsaasRequestException on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    iterator_to_array($connector->all('/v3/payments', [], ConnectorTestResponse::class));
})->throws(AsaasRequestException::class, 'cURL error 28: Connection timed out');

it('all() throws AsaasRequestException on connection exception mid-pagination', function (): void {
    $page1 = json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true);

    Http::fakeSequence()
        ->push($page1, 200)
        ->whenEmpty(fn (): never => throw new ConnectionException('cURL error 28: Connection timed out'));

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    iterator_to_array($connector->all('/v3/payments', ['limit' => 10], ConnectorTestResponse::class));
})->throws(AsaasRequestException::class, 'cURL error 28: Connection timed out');

it('throw() on connection failure result throws AsaasRequestException with status 0', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->get('/v3/payments/pay_123', [], ConnectorTestResponse::class);

    expect($result->success)->toBeFalse();

    $result->throw();
})->throws(AsaasRequestException::class, 'cURL error 28: Connection timed out');
