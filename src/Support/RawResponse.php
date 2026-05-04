<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

/** @internal The underlying Response is intentionally not exposed to prevent API key leakage via request headers. */
final readonly class RawResponse
{
    public function __construct(private Response $response) {}

    /** @return array{status: int, headers: array<string, list<string>>, body: string} */
    public function __debugInfo(): array
    {
        $body = $this->body();
        $length = mb_strlen($body);
        $limit = 350;

        return [
            'status' => $this->status(),
            'headers' => $this->headers(),
            'body' => $length <= $limit
                ? $body
                : mb_substr($body, 0, $limit).'... <truncated; '.$length.' chars total>',
        ];
    }

    public function status(): int
    {
        return $this->response->status();
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        /** @var array<string, list<string>> */
        return $this->response->headers();
    }

    public function header(string $key): ?string
    {
        $value = $this->response->header($key);

        return $value === '' ? null : $value;
    }

    public function body(): string
    {
        return $this->response->body();
    }

    /** @param array<string, string> $headers */
    public static function fake(int $status = 200, array $headers = [], string $body = ''): self
    {
        return new self(new Response(new Psr7Response($status, $headers, $body)));
    }
}
