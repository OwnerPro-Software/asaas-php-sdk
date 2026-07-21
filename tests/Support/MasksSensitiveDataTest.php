<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\MasksSensitiveData;
use OwnerPro\Asaas\Tests\Support\Fixtures\MaskableFixture;

mutates(MasksSensitiveData::class);

it('jsonSerialize delegates to __debugInfo', function (): void {
    $object = new class implements JsonSerializable
    {
        use MasksSensitiveData;

        public function __debugInfo(): array
        {
            return ['field' => '***'];
        }
    };

    expect($object->jsonSerialize())->toBe(['field' => '***']);
});

it('json_encode uses masked data from __debugInfo', function (): void {
    $object = new class implements JsonSerializable
    {
        use MasksSensitiveData;

        public function __debugInfo(): array
        {
            return ['secret' => '***', 'visible' => 'hello'];
        }
    };

    expect(json_encode($object))->toBe('{"secret":"***","visible":"hello"}');
});

it('__toString prefixes class name and uses masked __debugInfo', function (): void {
    $object = new class implements JsonSerializable
    {
        use MasksSensitiveData;

        public function __debugInfo(): array
        {
            return ['secret' => '***', 'visible' => 'hello'];
        }
    };

    expect((string) $object)->toBe($object::class.'({"secret":"***","visible":"hello"})');
});

it('__toString preserves slashes and unicode unescaped', function (): void {
    $object = new class implements JsonSerializable
    {
        use MasksSensitiveData;

        public function __debugInfo(): array
        {
            return ['url' => 'https://example.com/path', 'name' => 'José'];
        }
    };

    expect((string) $object)
        ->toContain('"url":"https://example.com/path"')
        ->toContain('"name":"José"');
});

it('__serialize throws so jobs/caches/sessions fail fast at dispatch instead of at the worker', function (): void {
    $object = new MaskableFixture('raw-secret', 'hello');

    expect(fn () => serialize($object))
        ->toThrow(LogicException::class);
});

it('__serialize message starts with the concrete class name', function (): void {
    $object = new MaskableFixture('raw-secret', 'hello');

    try {
        $object->__serialize();
        $this->fail('Expected LogicException');
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toStartWith(MaskableFixture::class.' cannot be serialized');
    }
});

it('__unserialize message starts with the concrete class name', function (): void {
    $object = new MaskableFixture('raw-secret', 'hello');

    try {
        $object->__unserialize([]);
        $this->fail('Expected LogicException');
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toStartWith(MaskableFixture::class.' cannot be unserialized');
    }
});

it('__serialize throw includes the concrete class name to make the source obvious', function (): void {
    $object = new MaskableFixture('raw-secret', 'hello');

    expect(fn () => $object->__serialize())
        ->toThrow(LogicException::class, MaskableFixture::class);
});

it('__unserialize throws to block restoration even from manually-crafted payloads', function (): void {
    $payload = 'O:'.strlen(MaskableFixture::class).':"'.MaskableFixture::class.'":1:{i:0;a:0:{}}';

    expect(fn () => unserialize($payload))
        ->toThrow(LogicException::class, 'cannot be unserialized');
});

it('mask keeps the specified number of trailing characters visible', function (): void {
    $object = new class implements JsonSerializable
    {
        use MasksSensitiveData;

        public function __debugInfo(): array
        {
            return [];
        }

        /** @return array<int, string> */
        public function results(): array
        {
            return [
                self::mask('12345678901', 3),
                self::mask('4111111111111111', 4),
                self::mask('56789', 2),
                self::mask('ab', 3),
                self::mask('', 3),
            ];
        }
    };

    $results = $object->results();

    expect($results[0])->toBe('********901');
    expect($results[1])->toBe('********1111');
    expect($results[2])->toBe('********89');
    expect($results[3])->toBe('********');
    // An empty value stays empty: a fill would disguise an unset field as a
    // redacted one and hide empty-payload bugs from the dump.
    expect($results[4])->toBe('');
});

it('mask hides the length of the value, so inputs of different sizes are indistinguishable', function (): void {
    $object = new class implements JsonSerializable
    {
        use MasksSensitiveData;

        public function __debugInfo(): array
        {
            return [];
        }

        public function exposeMask(string $value, int $visibleSuffix): string
        {
            return self::mask($value, $visibleSuffix);
        }
    };

    // A proportional fill would publish the exact secret length: an 11-digit
    // CPF and a 14-digit CNPJ sharing a suffix would be told apart by counting
    // asterisks. With a constant fill they render identically.
    expect($object->exposeMask('12345678901', 3))
        ->toBe($object->exposeMask('12345678000901', 3));

    expect($object->exposeMask(str_repeat('9', 64).'abc', 3))->toBe('********abc');
});

it('mask fully masks when string length equals visibleSuffix', function (): void {
    $object = new class implements JsonSerializable
    {
        use MasksSensitiveData;

        public function __debugInfo(): array
        {
            return [];
        }

        public function exposeMask(string $value, int $visibleSuffix): string
        {
            return self::mask($value, $visibleSuffix);
        }
    };

    expect($object->exposeMask('abc', 3))->toBe('********');
    expect($object->exposeMask('abcd', 3))->toBe('********bcd');
});
