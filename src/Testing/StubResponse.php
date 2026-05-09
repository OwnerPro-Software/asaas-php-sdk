<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;

final class StubResponse
{
    /**
     * @param  array<string, mixed>|PromiseInterface|Closure  $stub
     */
    public static function normalize(array|PromiseInterface|Closure $stub): PromiseInterface|Closure
    {
        if ($stub instanceof PromiseInterface || $stub instanceof Closure) {
            return $stub;
        }

        return Http::response(self::inferPagination($stub));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function inferPagination(array $body): array
    {
        if (! array_key_exists('data', $body)) {
            return $body;
        }

        if (! is_array($body['data']) || ! array_is_list($body['data'])) {
            return $body;
        }

        if (array_key_exists('hasMore', $body) || array_key_exists('totalCount', $body)) {
            return $body;
        }

        $count = count($body['data']);

        return [
            'object' => $body['object'] ?? 'list',
            'hasMore' => false,
            'totalCount' => $count,
            'limit' => $body['limit'] ?? $count,
            'offset' => $body['offset'] ?? 0,
            'data' => $body['data'],
        ];
    }
}
