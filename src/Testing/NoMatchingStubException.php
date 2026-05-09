<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use RuntimeException;

final class NoMatchingStubException extends RuntimeException
{
    /** @param  list<string>  $registered */
    public static function for(string $method, string $url, array $registered): self
    {
        $list = $registered === []
            ? '  (none)'
            : implode("\n", array_map(static fn (string $p): string => '  - '.$p, $registered));

        return new self(
            "No stub matched {$method} {$url}\n\n"
            ."Registered stubs:\n"
            .$list."\n\n"
            ."Hint: register a stub via AsaasClient::fake([...]) or ->stub('pattern', ...). "
            .'Other helpers: ->stubError(), ->stubException(), ->stubPages().',
        );
    }
}
