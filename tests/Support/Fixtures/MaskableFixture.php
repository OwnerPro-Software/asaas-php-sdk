<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Tests\Support\Fixtures;

use JsonSerializable;
use OwnerPro\Asaas\Support\MasksSensitiveData;

final readonly class MaskableFixture implements JsonSerializable
{
    use MasksSensitiveData;

    public function __construct(
        public string $secret,
        public string $visible,
    ) {}

    /** @return array{secret: string, visible: string} */
    public function __debugInfo(): array
    {
        return [
            'secret' => '***',
            'visible' => $this->visible,
        ];
    }
}
