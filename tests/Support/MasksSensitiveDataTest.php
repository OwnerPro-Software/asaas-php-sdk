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
