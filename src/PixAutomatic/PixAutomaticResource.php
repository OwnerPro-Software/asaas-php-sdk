<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixAutomatic;

use Generator;
use OwnerPro\Asaas\PixAutomatic\Request\AuthorizationRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class PixAutomaticResource
{
    private const string AUTHORIZATIONS = '/pix/automatic/authorizations';

    private const string PAYMENT_INSTRUCTIONS = '/pix/automatic/paymentInstructions';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|AuthorizationRequest $data */
    public function createAuthorization(array|AuthorizationRequest $data): AsaasResult
    {
        return $this->connector->post(self::AUTHORIZATIONS, AuthorizationRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function listAuthorizations(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::AUTHORIZATIONS, $query);
    }

    public function findAuthorization(string $id): AsaasResult
    {
        return $this->connector->get($this->authorizationPath($id));
    }

    public function cancelAuthorization(string $id): AsaasResult
    {
        return $this->connector->delete($this->authorizationPath($id));
    }

    /** @param array<string, mixed> $query */
    public function listPaymentInstructions(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::PAYMENT_INSTRUCTIONS, $query);
    }

    public function findPaymentInstruction(string $id): AsaasResult
    {
        return $this->connector->get($this->paymentInstructionPath($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function allAuthorizations(array $filters = []): Generator
    {
        return $this->connector->all(self::AUTHORIZATIONS, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function allPaymentInstructions(array $filters = []): Generator
    {
        return $this->connector->all(self::PAYMENT_INSTRUCTIONS, $filters);
    }

    private function authorizationPath(string $id, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::AUTHORIZATIONS, IdGuard::validate($id), $suffix);
    }

    private function paymentInstructionPath(string $id, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::PAYMENT_INSTRUCTIONS, IdGuard::validate($id), $suffix);
    }
}
