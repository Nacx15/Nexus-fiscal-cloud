<?php

namespace App\Infrastructure\Pac;

use App\Application\Fiscal\Contracts\PacClient;
use App\Application\Fiscal\Data\PacStampResult;
use Illuminate\Support\Str;

final class FakePacClient implements PacClient
{
    public function stamp(
        string $cfdiXml
    ): PacStampResult {
        return new PacStampResult(
            uuid:
                strtoupper(
                    Str::uuid()->toString()
                ),

            stampedXml:
                $cfdiXml,

            stampedAt:
                now()->toImmutable(),

            transactionId:
                'fake-' . Str::uuid()->toString(),
        );
    }
}
