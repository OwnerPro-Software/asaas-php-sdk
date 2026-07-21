<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Support\ErrorEnvelope;
use OwnerPro\Asaas\Support\PaginatesResults;

mutates(AsaasConnector::class, PaginatesResults::class, ErrorEnvelope::class);

it('implements Connector interface', function (): void {
    expect(AsaasConnector::class)->toImplement(Connector::class);
});

it('__debugInfo() exposes only baseUrl (no PendingRequest, no api key leak)', function (): void {
    $connector = new AsaasConnector(new PendingRequest, '', false);

    expect($connector->__debugInfo())->toBe(['baseUrl' => '']);
});

it('refuses to serialize, keeping the api key out of queue and cache payloads', function (): void {
    $connector = AsaasConnector::forLaravel('super-secret-key', Environment::Sandbox, 30);

    expect(fn (): string => serialize($connector))
        ->toThrow(LogicException::class, AsaasConnector::class.' cannot be serialized');
});

it('refuses to unserialize', function (): void {
    $connector = AsaasConnector::forLaravel('super-secret-key', Environment::Sandbox, 30);

    expect(function () use ($connector): void {
        $connector->__unserialize(['baseUrl' => 'https://evil.example']);
    })->toThrow(LogicException::class, AsaasConnector::class.' cannot be unserialized.');
});

// --- forLaravel factory ---

it('forLaravel accepts string environment', function (): void {
    Http::fake(['https://api.asaas.com/*' => Http::response(['id' => 'x', 'status' => 'OK'], 200)]);

    $connector = AsaasConnector::forLaravel('key', 'production', 30);
    $result = $connector->get('/payments/x');

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.asaas.com/'));
});

it('forLaravel throws on empty api key', function (): void {
    AsaasConnector::forLaravel('', 'sandbox', 30);
})->throws(InvalidArgumentException::class, 'The API key must not be empty.');

it('forLaravel throws on invalid environment string', function (): void {
    AsaasConnector::forLaravel('key', 'invalid', 30);
})->throws(ValueError::class);

it('forLaravel uses sandbox base url', function (): void {
    Http::fake(['https://api-sandbox.asaas.com/*' => Http::response(['id' => 'x', 'status' => 'OK'], 200)]);

    $connector = AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
    $result = $connector->get('/payments/x');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('x');

    Http::assertSent(function ($request): bool {
        return $request->hasHeader('access_token', 'test-key')
            && str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/');
    });
});

it('forLaravel uses production base url', function (): void {
    Http::fake(['https://api.asaas.com/*' => Http::response(['id' => 'x', 'status' => 'OK'], 200)]);

    $connector = AsaasConnector::forLaravel('prod-key', Environment::Production, 30);
    $result = $connector->get('/payments/x');

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

it('forStandalone throws on empty api key', function (): void {
    AsaasConnector::forStandalone('', 'sandbox', 30);
})->throws(InvalidArgumentException::class, 'The API key must not be empty.');

it('forStandalone throws on invalid environment string', function (): void {
    AsaasConnector::forStandalone('key', 'invalid', 30);
})->throws(ValueError::class);

it('forStandalone rejects request timeout of 0 (Guzzle would treat it as unlimited)', function (): void {
    AsaasConnector::forStandalone('key', 'sandbox', 0);
})->throws(InvalidArgumentException::class, 'Request timeout must be at least 1 second; got 0.');

it('forStandalone rejects negative request timeout', function (): void {
    AsaasConnector::forStandalone('key', 'sandbox', -5);
})->throws(InvalidArgumentException::class, 'Request timeout must be at least 1 second; got -5.');

it('forStandalone rejects connect timeout of 0', function (): void {
    AsaasConnector::forStandalone('key', 'sandbox', 30, 0);
})->throws(InvalidArgumentException::class, 'Connect timeout must be at least 1 second; got 0.');

it('forStandalone rejects negative connect timeout', function (): void {
    AsaasConnector::forStandalone('key', 'sandbox', 30, -3);
})->throws(InvalidArgumentException::class, 'Connect timeout must be at least 1 second; got -3.');

it('forStandalone creates connector for sandbox', function (): void {
    $connector = AsaasConnector::forStandalone('test-key', Environment::Sandbox, 30);

    expect($connector)->toBeInstanceOf(AsaasConnector::class);
});

it('forStandalone creates connector for production', function (): void {
    $connector = AsaasConnector::forStandalone('test-key', Environment::Production, 30);

    expect($connector)->toBeInstanceOf(AsaasConnector::class);
});

it('forLaravel configures default connect timeout', function (): void {
    Http::fake();
    $connector = AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);

    $reflection = new ReflectionClass($connector);
    $property = $reflection->getProperty('pendingRequest');
    $pendingRequest = $property->getValue($connector);

    expect($pendingRequest->getOptions()['connect_timeout'])->toBe(10);
});

it('forStandalone configures default connect timeout', function (): void {
    $connector = AsaasConnector::forStandalone('test-key', Environment::Sandbox, 30);

    $reflection = new ReflectionClass($connector);
    $property = $reflection->getProperty('pendingRequest');
    $pendingRequest = $property->getValue($connector);

    expect($pendingRequest->getOptions()['connect_timeout'])->toBe(10);
});

it('forStandalone accepts custom connect timeout', function (): void {
    $connector = AsaasConnector::forStandalone('test-key', Environment::Sandbox, 30, 5);

    $reflection = new ReflectionClass($connector);
    $property = $reflection->getProperty('pendingRequest');
    $pendingRequest = $property->getValue($connector);

    expect($pendingRequest->getOptions()['connect_timeout'])->toBe(5);
});

it('forStandalone enforces TLS certificate verification', function (): void {
    $connector = AsaasConnector::forStandalone('test-key', Environment::Sandbox, 30);

    $reflection = new ReflectionClass($connector);
    $property = $reflection->getProperty('pendingRequest');
    $pendingRequest = $property->getValue($connector);

    expect($pendingRequest->getOptions()['verify'])->toBeTrue();
});

it('forLaravel enforces TLS certificate verification', function (): void {
    Http::fake();
    $connector = AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);

    $reflection = new ReflectionClass($connector);
    $property = $reflection->getProperty('pendingRequest');
    $pendingRequest = $property->getValue($connector);

    expect($pendingRequest->getOptions()['verify'])->toBeTrue();
});

// --- Standalone HTTP behavior via DI constructor with stubbed PendingRequest ---

it('standalone get returns success result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['id' => 'pay_123', 'status' => 'PENDING'], 200)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/pay_123');

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pay_123');
    expect($result->response->status())->toBe(200);
});

it('standalone get returns empty data on scalar JSON response', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response('true', 200)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/pay_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
    expect($result->response->body())->toBe('true');
});

it('standalone post returns success result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['id' => 'pay_new', 'status' => 'PENDING'], 200)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->post('/payments', ['value' => 100]);

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pay_new');
});

it('standalone put returns success result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['id' => 'pay_123', 'status' => 'UPDATED'], 200)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->put('/payments/pay_123', ['value' => 200]);

    expect($result->success)->toBeTrue();
    expect($result->data['status'])->toBe('UPDATED');
});

it('standalone delete returns success result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['deleted' => true, 'id' => 'pay_123'], 200)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->delete('/payments/pay_123');

    expect($result->success)->toBeTrue();
});

it('standalone returns failure result on error response', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(
            json_decode(file_get_contents(__DIR__.'/../Fixtures/error_400.json'), true),
            400,
        )]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->post('/payments', ['bad' => 'data']);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
    expect($result->data)->toBeNull();
});

it('standalone paginate returns paginated result', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(
            json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true),
            200,
        )]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->paginate('/payments', ['limit' => 10]);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0]['id'])->toBe('pay_1');
    expect($result->totalCount)->toBe(50);
    expect($result->hasMore)->toBeTrue();
});

it('standalone returns fallback error when error response has no errors array', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response('Internal Server Error', 500)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->post('/payments', ['bad' => 'data']);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(500);
    expect($result->errors)->toBe([['code' => 'UNKNOWN_ERROR', 'description' => 'Internal Server Error']]);
    expect($result->data)->toBeNull();
});

it('synthesizes UNKNOWN_ERROR when error envelope has empty errors array', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(
            json_decode(file_get_contents(__DIR__.'/../Fixtures/error_empty_errors.json'), true),
            403,
        )]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/pay_123');

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(403);
    expect($result->errors)->toBe([['code' => 'UNKNOWN_ERROR', 'description' => 'Asaas returned empty errors array (status 403)']]);
    expect($result->data)->toBeNull();
});

it('propagates error envelope shape across documented and undocumented status codes', function (int $status, array $fixture): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response($fixture, $status)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->post('/payments', ['bad' => 'data']);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe($status);
    expect($result->errors)->toBe($fixture['errors']);
    expect($result->errors[0])->toHaveKey('code');
    expect($result->errors[0])->toHaveKey('description');
    expect($result->data)->toBeNull();
})->with('error_envelope_fixture');

it('exposes Retry-After header from 429 response through AsaasResult', function (): void {
    $fixture = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/error_429.json'), true);

    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response($fixture, 429, ['Retry-After' => '30'])]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/pix/tokenBucket/addressKey');

    expect($result->success)->toBeFalse();
    expect($result->response)->not->toBeNull();
    expect($result->response->status())->toBe(429);
    expect($result->response->header('Retry-After'))->toBe('30');
    expect($result->errors[0]['code'])->toBe('rate_limit_exceeded');
    expect($result->errors[0]['description'])->toBe('Too many requests. Please retry after the period indicated by the Retry-After header.');
});

it('synthesizes UNKNOWN_ERROR from {message: ...} shape when errors key is absent', function (): void {
    $fixture = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/error_message_shape.json'), true);

    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response($fixture, 503)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/pay_123');

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(503);
    expect($result->errors)->toBe([['code' => 'UNKNOWN_ERROR', 'description' => 'some error']]);
    expect($result->data)->toBeNull();
});

it('falls through to body fallback when {message: ""} is empty string', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(['message' => ''], 503)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/pay_456');

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(503);
    expect($result->errors[0]['code'])->toBe('UNKNOWN_ERROR');
    expect($result->errors[0]['description'])->not->toBe('');
    expect($result->errors[0]['description'])->toContain('message');
});

it('propagates 403 error envelope with code and description', function (): void {
    $fixture = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/error_403.json'), true);

    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response($fixture, 403)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments');

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(403);
    expect($result->errors[0]['code'])->toBe('forbidden');
    expect($result->errors[0]['description'])->toBe('You are not allowed to access this resource.');
    expect($result->data)->toBeNull();
});

it('propagates 404 error envelope with code and description', function (): void {
    $fixture = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/error_404.json'), true);

    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response($fixture, 404)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/invalid');

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(404);
    expect($result->errors[0]['code'])->toBe('not_found');
    expect($result->errors[0]['description'])->toBe('The requested resource was not found.');
    expect($result->data)->toBeNull();
});

it('orFail() surfaces synthesized description when errors array is empty', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response(
            json_decode(file_get_contents(__DIR__.'/../Fixtures/error_empty_errors.json'), true),
            500,
        )]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/pay_123');

    expect($result->success)->toBeFalse();

    $result->orFail();
})->throws(AsaasRequestException::class, 'Asaas returned empty errors array (status 500)');

it('strips HTML tags and truncates long non-JSON error bodies', function (): void {
    $htmlBody = '<html><head><title>502 Bad Gateway</title></head><body><h1>502 Bad Gateway</h1><p>nginx/1.24.0</p>'.str_repeat('<p>extra</p>', 200).'</body></html>';

    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response($htmlBody, 502)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/pay_123');

    expect($result->success)->toBeFalse();
    $description = $result->errors[0]['description'];
    expect($description)->not->toContain('<html>');
    expect($description)->not->toContain('<p>');
    expect(mb_strlen($description))->toBe(350);
    expect($description)->toStartWith('502 Bad Gateway');
});

it('standalone get returns failure result on connection exception', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/pay_123');

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
    expect($result->data)->toBeNull();
});

it('standalone paginate returns failure result on connection exception', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options): never => throw new ConnectionException('cURL error 7: Failed to connect')]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->paginate('/payments', []);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
    expect($result->data)->toBe([]);
});

// --- Laravel HTTP behavior (existing tests adapted to forLaravel) ---

it('returns AsaasResult with response on successful GET', function (): void {
    Http::fake(['*' => Http::response(
        json_decode(file_get_contents(__DIR__.'/../Fixtures/payment.json'), true),
        200
    )]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->get('/payments/pay_123');

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pay_123');
    expect($result->response->status())->toBe(200);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_123'
        && $request->method() === 'GET');
});

it('returns AsaasResult with response on successful POST', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_new', 'status' => 'PENDING'], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->post('/payments', ['value' => 100]);

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pay_new');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
        && $request->method() === 'POST');
});

it('returns AsaasResult with response on successful PUT', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_123', 'status' => 'UPDATED'], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->put('/payments/pay_123', ['value' => 200]);

    expect($result->success)->toBeTrue();
    expect($result->data['status'])->toBe('UPDATED');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_123'
        && $request->method() === 'PUT');
});

it('returns AsaasResult on DELETE', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true, 'id' => 'pay_123'], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->delete('/payments/pay_123');

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
    $result = $connector->post('/payments', ['bad' => 'data']);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(400);
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
    $result = $connector->paginate('/payments', ['limit' => 10]);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0]['id'])->toBe('pay_1');
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
    $result = $connector->paginate('/payments', []);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(400);

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
    $items = iterator_to_array($connector->all('/payments', ['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0]['id'])->toBe('pay_1');
    expect($items[2]['id'])->toBe('pay_3');

    Http::assertSentCount(2);
});

it('all() uses default limit of 100 when not specified', function (): void {
    $page = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 1, 'limit' => 100, 'offset' => 0,
        'data' => [['id' => 'pay_1', 'status' => 'DONE']],
    ];

    Http::fake(['*' => Http::response($page, 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/payments', []));

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
    $items = iterator_to_array($connector->all('/payments', ['limit' => 2]));

    expect($items)->toHaveCount(3);
    expect($items[0]['id'])->toBe('a1');
    expect($items[2]['id'])->toBe('a3');

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
    $items = iterator_to_array($connector->all('/payments', ['limit' => 0]));

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
    $items = iterator_to_array($connector->all('/payments', ['limit' => -5]));

    expect($items)->toHaveCount(1);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'limit=1'));
});

it('all() keeps walking when the envelope omits limit and offset', function (): void {
    Http::fakeSequence()
        ->push(['hasMore' => true, 'totalCount' => 3, 'data' => [['id' => 'a']]], 200)
        ->push(['hasMore' => true, 'totalCount' => 3, 'data' => [['id' => 'b']]], 200)
        ->push(['hasMore' => false, 'totalCount' => 3, 'data' => [['id' => 'c']]], 200);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/payments', []));

    expect(array_column($items, 'id'))->toBe(['a', 'b', 'c']);

    $offsets = Http::recorded()
        ->map(fn ($pair): string => (string) $pair[0]->url())
        ->map(fn (string $url): string => (string) parse_url($url, PHP_URL_QUERY))
        ->all();

    expect($offsets[0])->toContain('offset=0');
    expect($offsets[1])->toContain('offset=1');
    expect($offsets[2])->toContain('offset=2');
});

it('all() advances by rows delivered, not by the echoed limit', function (): void {
    Http::fakeSequence()
        ->push(['hasMore' => true, 'totalCount' => 2, 'limit' => 100, 'offset' => 0, 'data' => [['id' => 'a']]], 200)
        ->push(['hasMore' => false, 'totalCount' => 2, 'limit' => 100, 'offset' => 1, 'data' => [['id' => 'b']]], 200);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/payments', ['limit' => 100]));

    expect(array_column($items, 'id'))->toBe(['a', 'b']);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'offset=1'));
});

it('all() stops when API returns hasMore true with empty data', function (): void {
    $emptyPage = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 10, 'limit' => 10, 'offset' => 0,
        'data' => [],
    ];

    Http::fake(['*' => Http::response($emptyPage, 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/payments', []));

    expect($items)->toBeEmpty();

    Http::assertSentCount(1);
});

it('all() yields AsaasPaginatedError on error during pagination', function (): void {
    $page1 = json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true);
    $errorResponse = json_decode(file_get_contents(__DIR__.'/../Fixtures/error_400.json'), true);

    Http::fakeSequence()->push($page1, 200)->push($errorResponse, 400);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/payments', ['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0]['id'])->toBe('pay_1');
    expect($items[1]['id'])->toBe('pay_2');
    expect($items[2])->toBeInstanceOf(AsaasPaginatedError::class);
    expect($items[2]->errors[0]['description'])->toBe('The value field is required');
    expect($items[2]->response)->not->toBeNull();
    expect($items[2]->response->status())->toBe(400);
    // The fixture delivers 2 rows against limit=10, so the failed page was
    // requested at offset 2 — the first row not yet read, not offset+limit.
    expect($items[2]->offset)->toBe(2);
    expect($items[2]->limit)->toBe(10);
});

it('paginate uses defaults for missing pagination fields', function (): void {
    Http::fake(['*' => Http::response([
        'data' => [['id' => 'x', 'status' => 'OK']],
    ], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/payments', []);

    expect($result->success)->toBeTrue();
    expect($result->totalCount)->toBe(0);
    expect($result->hasMore)->toBeFalse();
    expect($result->limit)->toBe(0);
    expect($result->offset)->toBe(0);
    expect($result->data)->toHaveCount(1);
});

it('returns fallback error when error response has no errors array', function (): void {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->post('/payments', ['bad' => 'data']);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(500);
    expect($result->errors)->toBe([['code' => 'UNKNOWN_ERROR', 'description' => 'Internal Server Error']]);
    expect($result->data)->toBeNull();
});

it('paginate returns fallback error when error response has no errors array', function (): void {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/payments', []);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(500);
    expect($result->errors)->toBe([['code' => 'UNKNOWN_ERROR', 'description' => 'Internal Server Error']]);
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
    $result = $connector->paginate('/payments', ['limit' => 2]);

    expect($result->hasMore)->toBeTrue();

    $next = $result->next();

    expect($next)->not->toBeNull();
    expect($next->data)->toHaveCount(1);
    expect($next->data[0]['id'])->toBe('x3');
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
    $result = $connector->get('/payments/x');

    expect($result->success)->toBeTrue();
    expect($result->response->status())->toBe(200);
});

it('standalone handles 2xx with no JSON body', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn ($request, $options) => Factory::response('', 200)]);

    $connector = new AsaasConnector($pendingRequest, '', false);
    $result = $connector->get('/payments/x');

    expect($result->success)->toBeTrue();
    expect($result->response->status())->toBe(200);
});

// --- ConnectionException handling (Laravel mode) ---

it('get returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->get('/payments/pay_123');

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
    expect($result->data)->toBeNull();
});

it('post returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('Connection refused')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->post('/payments', ['value' => 100]);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
    expect($result->data)->toBeNull();
});

it('put returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('DNS resolution failed')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->put('/payments/pay_123', ['value' => 200]);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
    expect($result->data)->toBeNull();
});

it('delete returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('Connection reset by peer')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->delete('/payments/pay_123');

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
    expect($result->data)->toBeNull();
});

it('paginate returns failure result on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('cURL error 7: Failed to connect')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->paginate('/payments', []);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
    expect($result->data)->toBe([]);
});

it('all() yields AsaasPaginatedError on connection exception', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/payments', []));

    expect($items)->toHaveCount(1);
    expect($items[0])->toBeInstanceOf(AsaasPaginatedError::class);
    expect($items[0]->errors[0]['description'])->toBe('Unable to connect to the Asaas API.');
    expect($items[0]->response)->toBeNull();
    expect($items[0]->offset)->toBe(0);
    expect($items[0]->limit)->toBe(100);
});

it('all() yields AsaasPaginatedError on connection exception mid-pagination', function (): void {
    $page1 = json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true);

    Http::fakeSequence()
        ->push($page1, 200)
        ->whenEmpty(fn (): never => throw new ConnectionException('cURL error 28: Connection timed out'));

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $items = iterator_to_array($connector->all('/payments', ['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0]['id'])->toBe('pay_1');
    expect($items[1]['id'])->toBe('pay_2');
    expect($items[2])->toBeInstanceOf(AsaasPaginatedError::class);
    expect($items[2]->errors[0]['description'])->toBe('Unable to connect to the Asaas API.');
    expect($items[2]->response)->toBeNull();
    // The fixture delivers 2 rows against limit=10, so the failed page was
    // requested at offset 2 — the first row not yet read, not offset+limit.
    expect($items[2]->offset)->toBe(2);
    expect($items[2]->limit)->toBe(10);
});

it('hides the API key from debug output', function (): void {
    $connector = AsaasConnector::forStandalone('sk_live_super_secret_key_123', Environment::Sandbox, 30);

    $debug = $connector->__debugInfo();

    expect($debug)->toBe(['baseUrl' => 'https://api-sandbox.asaas.com/v3']);
    expect(print_r($connector, true))->not->toContain('sk_live_super_secret_key_123');
});

it('debug output shows production base url', function (): void {
    $connector = AsaasConnector::forStandalone('sk_live_key', Environment::Production, 30);

    $debug = $connector->__debugInfo();

    expect($debug)->toBe(['baseUrl' => 'https://api.asaas.com/v3']);
});

it('orFail() on connection failure result throws AsaasRequestException with status 0', function (): void {
    Http::fake(['*' => fn (): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);
    $result = $connector->get('/payments/pay_123');

    expect($result->success)->toBeFalse();

    $result->orFail();
})->throws(AsaasRequestException::class, 'Unable to connect to the Asaas API.');

it('accepts the minimum valid request timeout (1 second)', function (): void {
    $connector = AsaasConnector::forStandalone('key', Environment::Sandbox, 1);

    expect($connector)->toBeInstanceOf(AsaasConnector::class);
});

it('accepts the minimum valid connect timeout (1 second)', function (): void {
    $connector = AsaasConnector::forStandalone('key', Environment::Sandbox, 30, 1);

    expect($connector)->toBeInstanceOf(AsaasConnector::class);
});

it('routes standalone clients through the Laravel HTTP factory when one is bound', function (): void {
    // A bare `new PendingRequest` builds its own Guzzle client, so it bypasses
    // Http::fake() and preventStrayRequests(): a Laravel suite faking HTTP would
    // still issue live calls with real credentials through AsaasClient::for().
    Http::fake(['*' => Http::response(['id' => 'pay_1'])]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe(['id' => 'pay_1']);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_1');
});

it('still builds a standalone request when no facade root is set', function (): void {
    $application = Facade::getFacadeApplication();
    Facade::setFacadeApplication(null);

    try {
        expect(AsaasConnector::forStandalone('key', 'sandbox', 30))->toBeInstanceOf(AsaasConnector::class);
    } finally {
        Facade::setFacadeApplication($application);
    }
});

it('synthesizes a description when the error body is empty', function (): void {
    // Proxies, gateways and WAFs answer 502/504 with no body at all. Trimming
    // that to '' left AsaasRequestException with an empty message, since the
    // '?? Asaas API error' default only covers null.
    Http::fake(['*' => Http::response('', 502)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors)->toBe([[
        'code' => 'UNKNOWN_ERROR',
        'description' => 'Asaas returned status 502 with no readable error body.',
    ]]);
    expect(fn () => $result->orFail())->toThrow(AsaasRequestException::class, 'Asaas returned status 502 with no readable error body.');
});

it('falls back when errors is an object instead of a list', function (): void {
    // An object-shaped envelope has no index 0, so passing it through would
    // break every caller reading $errors[0]['description'].
    Http::fake(['*' => Http::response(['errors' => ['code' => 'X', 'description' => 'Y']], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors[0]['code'])->toBe('UNKNOWN_ERROR');
    expect($result->errors[0]['description'])->not->toBe('');
});

it('sends an empty body as a JSON object, not a JSON array', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    $connector = AsaasConnector::forStandalone('key', 'sandbox', 30);
    $connector->post('/payments/pay_1/refund');
    $connector->put('/payments/pay_1');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST' && $request->body() === '{}');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request->body() === '{}');
});

it('still sends a populated body as a JSON object', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    AsaasConnector::forStandalone('key', 'sandbox', 30)->post('/payments', ['value' => 100.0]);

    Http::assertSent(fn (Request $request): bool => $request->body() === '{"value":100}');
});

it('falls back when the errors list carries scalars instead of objects', function (): void {
    // A list of strings has an index 0, but reading ['description'] off it is a
    // TypeError — same breakage as the object-shaped envelope, so same fallback.
    Http::fake(['*' => Http::response(['errors' => ['boom']], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors[0]['code'])->toBe('UNKNOWN_ERROR');
    expect($result->errors[0]['description'])->toBe('{"errors":["boom"]}');
});

it('keeps the object when the errors list mixes one with a scalar', function (): void {
    // This used to fall back for the whole envelope. Dropping a readable error
    // because a sibling entry is junk hands the caller a dump of the body in
    // place of the diagnosis they needed; the scalar is dropped instead.
    Http::fake(['*' => Http::response(['errors' => [['code' => 'X', 'description' => 'Y'], 'boom']], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors)->toBe([['code' => 'X', 'description' => 'Y']]);
});

it('redacts credentials out of the synthesized error description', function (string $field): void {
    // A rejected POST /accounts answers with the subaccount payload, key
    // included, under a body shape that is not the canonical error envelope —
    // so the whole body becomes the description. $errors is the one part of a
    // result that no downstream scrub reaches: AsaasResult scrubs `data` by
    // field name, and a credential pasted into a description is a string.
    Http::fake(['*' => Http::response([$field => 'aact_prod_LIVEKEY123', 'status' => 'REJECTED'], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->post('/accounts');

    expect($result->errors[0]['description'])
        ->not->toContain('aact_prod_LIVEKEY123')
        ->toContain('***')
        ->toContain('REJECTED');
})->with(['apiKey', 'accessToken', 'authToken', 'creditCardToken']);

it('redacts a credential nested inside the synthesized error description', function (): void {
    Http::fake(['*' => Http::response(['account' => ['apiKey' => 'aact_prod_LIVEKEY123']], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->post('/accounts');

    expect($result->errors[0]['description'])->toBe('{"account":{"apiKey":"***"}}');
});

it('leaves a non-JSON error body alone, having no field names to key on', function (): void {
    Http::fake(['*' => Http::response('upstream refused the connection', 502)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors[0]['description'])->toBe('upstream refused the connection');
});

it('falls back when the error body is markup with no readable text', function (): void {
    // strip_tags() on a WAF page leaves only whitespace; without trimming that
    // reaches the caller's log as a blank exception message.
    Http::fake(['*' => Http::response('   <html>  <body> </body> </html>  ', 503)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors)->toBe([[
        'code' => 'UNKNOWN_ERROR',
        'description' => 'Asaas returned status 503 with no readable error body.',
    ]]);
});

it('falls back when an error row is a list rather than an object', function (): void {
    // `[[1, 2]]` survives an is_array() check but has no code/description to
    // read — no more an error object than a scalar row is.
    Http::fake(['*' => Http::response(['errors' => [[1, 2]]], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors[0]['code'])->toBe('UNKNOWN_ERROR');
    expect($result->errors[0]['description'])->toBe('{"errors":[[1,2]]}');
});

it('passes through a canonical row that simply carries no fields', function (): void {
    // `{}` and `[]` decode to the same PHP value, and the first is a legitimate
    // row: AsaasRequestException already substitutes its own message for it.
    Http::fake(['*' => Http::response(['errors' => [[]]], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors)->toBe([[]]);
});

it('keeps the readable error rows when a sibling row is malformed', function (): void {
    // Dropping the whole envelope over one bad entry threw away the error the
    // caller actually needed and replaced it with a dump of the body.
    Http::fake(['*' => Http::response([
        'errors' => [['code' => 'invalid_cpfCnpj', 'description' => 'CPF inválido'], [1, 2], 'boom'],
    ], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors)->toBe([['code' => 'invalid_cpfCnpj', 'description' => 'CPF inválido']]);
});

it('falls back only when no row in the list can be read as an error object', function (): void {
    Http::fake(['*' => Http::response(['errors' => [[1, 2], 'boom']], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect($result->errors[0]['code'])->toBe('UNKNOWN_ERROR');
    expect($result->errors[0]['description'])->toBe('{"errors":[[1,2],"boom"]}');
});

it('reindexes the surviving rows so errors stays a JSON list', function (): void {
    // The unreadable row comes first, so the survivor sits at key 1 until it is
    // reindexed — and a gapped array encodes as a JSON object, not a list.
    Http::fake(['*' => Http::response([
        'errors' => ['boom', ['code' => 'invalid_cpfCnpj', 'description' => 'CPF inválido']],
    ], 400)]);

    $result = AsaasConnector::forStandalone('key', 'sandbox', 30)->get('/payments/pay_1');

    expect(array_keys($result->errors))->toBe([0]);
    expect(json_encode($result->errors))->toStartWith('[{');
});
