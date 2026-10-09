<?php

namespace App\Domain\Fiscal\Exceptions;

use RuntimeException;
use Throwable;

final class PacStampingException extends RuntimeException
{
    public function __construct(
        public readonly ?string $providerCode,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            0,
            $previous
        );
    }
}
