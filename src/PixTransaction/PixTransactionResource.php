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
        return $this->connector->get(sprintf('%s/%s', self::TRANSACTIONS, IdGuard::validate($id)));
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/cancel', self::TRANSACTIONS, IdGuard::validate($id)));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all(self::TRANSACTIONS, $filters);
    }
}
