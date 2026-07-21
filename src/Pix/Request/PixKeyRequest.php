<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Pix\PixAddressKeyType;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class PixKeyRequest
{
    use HasArrayFactory;

    /**
     * @param  PixAddressKeyType|string  $type  must be `EVP`. `POST /v3/pix/addressKeys`
     *                                          declares `"enum": ["EVP"]` — Asaas only mints
     *                                          random keys through the API; CPF, CNPJ, EMAIL
     *                                          and PHONE keys are registered in the Asaas
     *                                          panel, not here. The shared
     *                                          {@see PixAddressKeyType} enum keeps all five
     *                                          cases because transfers legitimately accept
     *                                          them, so this DTO guards the narrower
     *                                          create-key contract itself.
     */
    public function __construct(
        public PixAddressKeyType|string $type,
    ) {
        $value = $type instanceof PixAddressKeyType ? $type->value : $type;

        if ($value !== PixAddressKeyType::Evp->value) {
            throw new InvalidArgumentException(sprintf(
                "PixKeyRequest: type must be 'EVP'; got '%s'. The Asaas API only creates random (EVP) keys — register CPF, CNPJ, EMAIL and PHONE keys in the Asaas panel.",
                $value,
            ));
        }
    }

    /** @param array{type?: PixAddressKeyType|string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            type: $data['type'] ?? throw new InvalidArgumentException('PixKeyRequest: type is required'),
        );
    }
}
