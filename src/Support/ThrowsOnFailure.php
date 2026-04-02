<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

trait ThrowsOnFailure
{
    public function throw(): self
    {
        if (! $this->success) {
            throw new AsaasRequestException($this->errors ?? [], $this->response);
        }

        return $this;
    }
}
