<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\FiscalInfo;

use OwnerPro\Asaas\FiscalInfo\Request\FiscalInfoRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;

final readonly class FiscalInfoResource
{
    private const string BASE = '/fiscalInfo';

    public function __construct(private Connector $connector) {}

    public function recover(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/');
    }

    /**
     * @param  array<string, mixed>|FiscalInfoRequest  $data
     * @param  string|resource|null  $certificateFile  Optional A1 digital certificate file (binary).
     */
    public function save(
        array|FiscalInfoRequest $data,
        mixed $certificateFile = null,
        ?string $certificateFilename = null,
    ): AsaasResult {
        $files = [];

        if ($certificateFile !== null) {
            $files[] = [
                'name' => 'certificateFile',
                'contents' => $certificateFile,
                'filename' => $certificateFilename ?? 'certificate.pfx',
            ];
        }

        return $this->connector->postMultipart(
            self::BASE.'/',
            FiscalInfoRequest::resolveData($data),
            $files,
        );
    }

    public function municipalOptions(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/municipalOptions');
    }

    /** @param array<string, mixed> $query */
    public function services(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE.'/services', $query);
    }

    /** @param array<string, mixed> $query */
    public function federalServiceCodes(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE.'/federalServiceCodes', $query);
    }

    /** @param array<string, mixed> $query */
    public function nbsCodes(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE.'/nbsCodes', $query);
    }

    /** @param array<string, mixed> $query */
    public function operationIndicatorCodes(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE.'/operationIndicatorCodes', $query);
    }

    /** @param array<string, mixed> $query */
    public function taxClassificationCodes(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE.'/taxClassificationCodes', $query);
    }

    /** @param array<string, mixed> $query */
    public function taxSituationCodes(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE.'/taxSituationCodes', $query);
    }

    public function configureNationalPortal(bool $enabled): AsaasResult
    {
        return $this->connector->post(self::BASE.'/nationalPortal', ['enabled' => $enabled]);
    }
}
