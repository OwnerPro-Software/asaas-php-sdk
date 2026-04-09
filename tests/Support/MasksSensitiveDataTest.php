<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\MasksSensitiveData;

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
    expect($results[1])->toBe('************1111');
    expect($results[2])->toBe('***89');
    expect($results[3])->toBe('**');
    expect($results[4])->toBe('');
});
