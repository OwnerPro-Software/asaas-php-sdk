<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

/**
 * Base type for transport-level failures: no complete, readable response was
 * received from the Asaas API. Thrown only when the connector is built with
 * `throwOnTransportFailure: true`. Catching a subclass tells the caller
 * whether a blind retry is safe (`RequestNotDeliveredException`) or whether
 * reconciliation is required first (`IndeterminateResultException`).
 */
abstract class TransportException extends AsaasException {}
