<?php

namespace App\Domain\Fiscal\Exceptions;

use RuntimeException;
use Throwable;

final class CfdiBuildException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            0,
            $previous
        );
    }
}
