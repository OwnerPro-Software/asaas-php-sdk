<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

/**
 * Assertion helpers for the recorded request/response stream.
 *
 * Extracted from FakeAsaasClient to keep that class focused on HTTP
 * orchestration; this trait consumes recorded(...) and resolvePattern(...).
 */
trait RecordsHttpAssertions
{
    /**
     * @return list<array{0: Request, 1: Response}>
     */
    abstract public function recorded(?string $pattern = null): array;

    public function assertSent(string|Closure $patternOrCallback, ?Closure $callback = null, ?int $times = null): self
    {
        [$pattern, $cb] = $this->normalizeSentArgs($patternOrCallback, $callback);

        $count = $this->countRecorded($pattern, $cb);

        if ($times !== null) {
            Assert::assertSame($times, $count, sprintf(
                'Expected %d request(s) matching %s, got %d.',
                $times,
                $pattern ?? '<callback>',
                $count,
            ));

            return $this;
        }

        Assert::assertGreaterThan(0, $count, sprintf(
            'Expected at least one request matching %s, got 0.',
            $pattern ?? '<callback>',
        ));

        return $this;
    }

    public function assertNotSent(string|Closure $patternOrCallback, ?Closure $callback = null): self
    {
        [$pattern, $cb] = $this->normalizeSentArgs($patternOrCallback, $callback);

        $count = $this->countRecorded($pattern, $cb);

        Assert::assertSame(0, $count, sprintf(
            'Expected no requests matching %s, got %d.',
            $pattern ?? '<callback>',
            $count,
        ));

        return $this;
    }

    public function assertSentCount(int $expected): self
    {
        $actual = count($this->recorded());

        Assert::assertSame($expected, $actual, sprintf(
            'Expected %d total request(s), got %d.',
            $expected,
            $actual,
        ));

        return $this;
    }

    public function assertNothingSent(): self
    {
        return $this->assertSentCount(0);
    }

    /** @param list<string|Closure> $matchers */
    public function assertSentInOrder(array $matchers): self
    {
        Assert::assertNotEmpty(
            $matchers,
            'assertSentInOrder requires at least one matcher; pass assertNothingSent() instead to assert no requests were made.',
        );

        $entries = $this->recorded();
        $cursor = 0;

        foreach ($matchers as $i => $matcher) {
            $cursor = $this->advanceCursor($entries, $cursor, $matcher, $i);
        }

        return $this;
    }

    abstract protected function resolvePattern(string $pattern): string;

    /**
     * @param  array{0: Request, 1: Response}  $entry
     */
    private function entryMatches(array $entry, string|Closure $matcher): bool
    {
        if ($matcher instanceof Closure) {
            return (bool) $matcher($entry[0], $entry[1]);
        }

        return Str::is(Str::start($this->resolvePattern($matcher), '*'), $entry[0]->url());
    }

    /**
     * @return array{0: ?string, 1: ?Closure}
     */
    private function normalizeSentArgs(string|Closure $patternOrCallback, ?Closure $callback): array
    {
        if ($patternOrCallback instanceof Closure) {
            return [null, $patternOrCallback];
        }

        return [$patternOrCallback, $callback];
    }

    private function countRecorded(?string $pattern, ?Closure $callback): int
    {
        $entries = $pattern === null ? $this->recorded() : $this->recorded($pattern);

        if (! $callback instanceof Closure) {
            return count($entries);
        }

        return count(array_filter(
            $entries,
            static fn (array $entry): bool => (bool) $callback($entry[0], $entry[1]),
        ));
    }

    /**
     * @param  list<array{0: Request, 1: Response}>  $entries
     */
    private function advanceCursor(array $entries, int $cursor, string|Closure $matcher, int $index): int
    {
        $remaining = array_slice($entries, $cursor, preserve_keys: false);

        $hit = -1;
        foreach ($remaining as $offset => $entry) {
            if ($this->entryMatches($entry, $matcher)) {
                $hit = $offset;
                break;
            }
        }

        Assert::assertGreaterThanOrEqual(0, $hit, sprintf(
            'assertSentInOrder: matcher #%d (%s) did not match in remaining requests.',
            $index,
            is_string($matcher) ? $matcher : '<callback>',
        ));

        return $cursor + $hit + 1;
    }
}
