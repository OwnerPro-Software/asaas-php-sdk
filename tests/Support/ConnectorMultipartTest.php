<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Environment;

mutates(AsaasConnector::class);

function multipartConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

it('sends multipart payload with attached files', function (): void {
    Http::fake(['*' => Http::response(['id' => 'file_1', 'status' => 'PROCESSING'], 200)]);

    $result = multipartConnector()->postMultipart(
        '/myAccount/documents/doc_1/files',
        ['type' => 'IDENTIFICATION'],
        [[
            'name' => 'documentFile',
            'contents' => 'binary-bytes',
            'filename' => 'rg.png',
        ]],
    );

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('file_1');

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/myAccount/documents/doc_1/files') {
            return false;
        }
        if (! str_contains((string) $request->header('Content-Type')[0], 'multipart/form-data')) {
            return false;
        }

        $body = (string) $request->body();
        if (! str_contains($body, 'IDENTIFICATION')) {
            return false;
        }

        return true;
    });
});

it('attaches multiple files', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    multipartConnector()->postMultipart(
        '/myAccount/documents/doc_1/files',
        [],
        [
            ['name' => 'documentFile', 'contents' => 'file-1-bytes', 'filename' => 'front.png'],
            ['name' => 'documentFile', 'contents' => 'file-2-bytes', 'filename' => 'back.png'],
        ],
    );

    Http::assertSent(function ($request): bool {
        $body = (string) $request->body();

        return str_contains($body, 'front.png') && str_contains($body, 'back.png');
    });
});

it('rejects empty files array', function (): void {
    multipartConnector()->postMultipart('/myAccount/documents/x/files', [], []);
})->throws(InvalidArgumentException::class, 'postMultipart requires at least one file.');

it('returns failure on multipart connection error', function (): void {
    Http::fake(fn () => throw new ConnectionException('boom'));

    $result = multipartConnector()->postMultipart('/myAccount/documents/x/files', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]);

    expect($result->success)->toBeFalse();
    expect($result->errors[0]['code'])->toBe('CONNECTION_ERROR');
});

it('returns failure when API rejects the upload', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['description' => 'invalid file']]], 400)]);

    $result = multipartConnector()->postMultipart('/myAccount/documents/x/files', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]);

    expect($result->success)->toBeFalse();
    expect($result->errors[0]['description'])->toBe('invalid file');
    expect($result->response->status())->toBe(400);
});

it('restores JSON body format after a multipart upload', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = multipartConnector();

    $connector->postMultipart('/upload', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]);

    $connector->post('/payments', ['value' => 100]);

    Http::assertSent(function ($request): bool {
        if (! str_contains($request->url(), '/payments')) {
            return true;
        }

        $contentType = (string) $request->header('Content-Type')[0];

        return str_contains($contentType, 'application/json');
    });
});

it('does not leak attached files between sequential multipart calls', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = multipartConnector();

    $connector->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'FIRST', 'filename' => 'first.png',
    ]]);
    $connector->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'SECOND', 'filename' => 'second.png',
    ]]);

    $requests = Http::recorded();
    $secondBody = (string) $requests[1][0]->body();

    expect($secondBody)->toContain('second.png');
    expect($secondBody)->not->toContain('first.png');
});

it('restores JSON body format even when the multipart upload fails', function (): void {
    Http::fakeSequence()
        ->push(['errors' => [['description' => 'rejected']]], 400)
        ->push(['ok' => true], 200);

    $connector = multipartConnector();

    $connector->postMultipart('/upload', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]);

    $connector->post('/payments', ['value' => 100]);

    Http::assertSent(function ($request): bool {
        if (! str_contains($request->url(), '/payments')) {
            return true;
        }

        return str_contains((string) $request->header('Content-Type')[0], 'application/json');
    });
});
