<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

use Generator;
use OwnerPro\Asaas\PixTransaction\Request\DecodeQrCodeRequest;
use OwnerPro\Asaas\PixTransaction\Request\PayQrCodeRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class PixTransactionResource
{
    private const string TRANSACTIONS = '/pix/transactions';

    private const string QR_CODES = '/pix/qrCodes';

    private const string RECURRINGS = '/pix/transactions/recurrings';

    private const string RECURRING_ITEMS = '/pix/transactions/recurrings/items';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|DecodeQrCodeRequest $data */
    public function decodeQrCode(array|DecodeQrCodeRequest $data): AsaasResult
    {
        return $this->connector->post(self::QR_CODES.'/decode', DecodeQrCodeRequest::resolveData($data));
    }

    /** @param array<string, mixed>|PayQrCodeRequest $data */
    public function payQrCode(array|PayQrCodeRequest $data): AsaasResult
    {
        return $this->connector->post(self::QR_CODES.'/pay', PayQrCodeRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::TRANSACTIONS, $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id));
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post($this->path($id, '/cancel'));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all(self::TRANSACTIONS, $filters);
    }

    /** @param array<string, mixed> $query */
    public function listRecurrings(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::RECURRINGS, $query);
    }

    public function findRecurring(string $id): AsaasResult
    {
        return $this->connector->get($this->recurringPath($id));
    }

    public function cancelRecurring(string $id): AsaasResult
    {
        return $this->connector->post($this->recurringPath($id, '/cancel'));
    }

    /** @param array<string, mixed> $query */
    public function listRecurringItems(string $id, array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate($this->recurringPath($id, '/items'), $query);
    }

    public function cancelRecurringItem(string $itemId): AsaasResult
    {
        return $this->connector->post($this->recurringItemPath($itemId, '/cancel'));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function allRecurrings(array $filters = []): Generator
    {
        return $this->connector->all(self::RECURRINGS, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function allRecurringItems(string $id, array $filters = []): Generator
    {
        return $this->connector->all($this->recurringPath($id, '/items'), $filters);
    }

    private function path(string $id, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::TRANSACTIONS, IdGuard::validate($id), $suffix);
    }

    private function recurringPath(string $id, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::RECURRINGS, IdGuard::validate($id), $suffix);
    }

    private function recurringItemPath(string $itemId, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::RECURRING_ITEMS, IdGuard::validate($itemId), $suffix);
    }
}
