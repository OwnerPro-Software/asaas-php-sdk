<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\SecretRedactor;

mutates(SecretRedactor::class);

it('replaces every credential-bearing field name', function (string $key): void {
    expect(SecretRedactor::scrub([$key => 'live-secret']))->toBe([$key => '***']);
})->with(['apiKey', 'accessToken', 'authToken', 'creditCardToken']);

it('leaves non-secret fields untouched', function (): void {
    $data = ['id' => 'acc_1', 'walletId' => 'w1', 'name' => 'John', 'value' => 100.5, 'active' => true];

    expect(SecretRedactor::scrub($data))->toBe($data);
});

it('matches the field name case-insensitively so a lowercased variant cannot slip through', function (): void {
    expect(SecretRedactor::scrub(['APIKEY' => 'k', 'apikey' => 'k', 'ApiKey' => 'k']))
        ->toBe(['APIKEY' => '***', 'apikey' => '***', 'ApiKey' => '***']);
});

it('scrubs secrets nested inside objects and rows', function (): void {
    $data = [
        'data' => [
            ['id' => 'w_1', 'authToken' => 'secret-one'],
            ['id' => 'w_2', 'authToken' => 'secret-two'],
        ],
        'account' => ['nested' => ['apiKey' => 'deep-secret']],
    ];

    expect(SecretRedactor::scrub($data))->toBe([
        'data' => [
            ['id' => 'w_1', 'authToken' => '***'],
            ['id' => 'w_2', 'authToken' => '***'],
        ],
        'account' => ['nested' => ['apiKey' => '***']],
    ]);
});

it('replaces a secret key whose value is an array instead of recursing into it', function (): void {
    // Recursing first would emit the parts of the secret unredacted.
    expect(SecretRedactor::scrub(['apiKey' => ['part' => 'live-secret']]))
        ->toBe(['apiKey' => '***']);
});

it('preserves list keys so a scrubbed page still encodes as a JSON array', function (): void {
    $scrubbed = SecretRedactor::scrub([['authToken' => 'a'], ['authToken' => 'b']]);

    expect(array_is_list($scrubbed))->toBeTrue()
        ->and(json_encode($scrubbed))->toBe('[{"authToken":"***"},{"authToken":"***"}]');
});

it('scrubJson redacts secrets inside a JSON body', function (): void {
    $body = '{"id":"acc_1","apiKey":"$aact_live_key"}';

    expect(SecretRedactor::scrubJson($body))->toBe('{"id":"acc_1","apiKey":"***"}');
});

it('scrubJson answers null for a body that is not a JSON array or object', function (string $body): void {
    expect(SecretRedactor::scrubJson($body))->toBeNull();
})->with([
    'empty' => '',
    'html' => '<html><body>502 Bad Gateway</body></html>',
    'plain text' => 'Service Unavailable',
    'json string' => '"just-a-string"',
    'json number' => '42',
    'json null' => 'null',
    'truncated json' => '{"id":"acc_1","apiKey":"$aact_l',
]);

it('scrubJson keeps slashes and unicode unescaped', function (): void {
    $body = '{"url":"https://example.com/path","name":"José"}';

    expect(SecretRedactor::scrubJson($body))->toBe($body);
});
