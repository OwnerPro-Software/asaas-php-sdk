<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

/**
 * Base type for transport-level failures: no complete, readable response was
 * received from the Asaas API — or the response received was a 5xx, which
 * reports that the server could not answer. Catching a subclass tells the
 * caller whether a blind retry is safe (`RequestNotDeliveredException`) or
 * whether reconciliation is required first (`IndeterminateResultException`).
 */
abstract class TransportException extends AsaasException {}
