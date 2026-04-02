<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

final readonly class RawResponse
{
    public function __construct(private Response $response) {}

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

    public function toUnderlying(): Response
    {
        return $this->response;
    }

    /** @param array<string, string> $headers */
    public static function fake(int $status = 200, array $headers = [], string $body = ''): self
    {
        return new self(new Response(new Psr7Response($status, $headers, $body)));
    }
}
