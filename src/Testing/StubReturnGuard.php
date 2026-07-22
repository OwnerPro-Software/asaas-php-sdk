<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;

/**
 * Turns whatever a callable stub returned into the promise Guzzle's handler
 * stack expects.
 *
 * Stubs registered as values are normalized once, at registration, by
 * {@see StubResponse::normalize()}. A Closure produces its value per request,
 * past that seam — and Laravel's `buildStubHandler()` mishandles two shapes of
 * it. It collects stub returns through `->filter()`, so every *falsy* one
 * (`null`, `false`, `0`, `''`, `[]`) is dropped and read as "no stub matched",
 * which hands the request to the real network handler. And it coerces only
 * arrays, so a string, an int or a `Response` reaches Guzzle unwrapped and dies
 * as `Call to a member function then() on string`.
 *
 * Every return therefore becomes one of two explicit outcomes here: a promise,
 * or a message naming the pattern and what came back.
 *
 * `null` is refused rather than read as an empty 200. A closure that returns
 * nothing is indistinguishable from one whose conditional fell through without
 * returning, and that mistake used to reach the live API; refusing it keeps the
 * loud-catch-all contract that {@see NoMatchingStubException} states for the
 * unmatched case. `Factory::response()` says "empty 200" when that is meant.
 */
final class StubReturnGuard
{
    public static function normalize(mixed $returned, string $pattern): PromiseInterface
    {
        if ($returned instanceof PromiseInterface) {
            return $returned;
        }

        // The shape `assertSent` hands back and the natural thing to return
        // when reshaping a recorded response — but the handler stack speaks
        // only promises, so it has to be rewrapped rather than passed along.
        if ($returned instanceof Response) {
            return Factory::response($returned->body(), $returned->status(), $returned->headers());
        }

        if (is_array($returned) || is_string($returned)) {
            return Factory::response($returned);
        }

        // One nowdoc rather than concatenated fragments: string concatenation
        // is mutable surface (ConcatSwitchSides / ConcatRemoveRight) that no
        // assertion on a message can kill, and the pieces carry no meaning
        // apart anyway.
        $message = <<<'TXT'
            The closure stub for "%s" returned %s.

            A closure stub must return a PromiseInterface (Http::response(...) / Factory::response(...)), an Illuminate\Http\Client\Response, an array body, or a string body.

            Hint: return Factory::response() for an empty 200. Returning null is refused because it cannot be told apart from a closure that fell through without returning — which used to send the request to the real Asaas API instead of failing the test.
            TXT;

        throw new InvalidArgumentException(sprintf($message, $pattern, get_debug_type($returned)));
    }
}
