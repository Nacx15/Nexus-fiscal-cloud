<?php

namespace App\Application\Fiscal;

use App\Models\FiscalSequence;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AllocateFiscalFolio
{
    public function execute(
        int $sequenceId,
        int $companyId
    ): int {
        return DB::transaction(
            function () use (
                $sequenceId,
                $companyId
            ): int {

                $sequence = FiscalSequence::query()
                    ->forCompany($companyId)
                    ->whereKey($sequenceId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($sequence->status !== 'active') {
                    throw new RuntimeException(
                        'Fiscal sequence is inactive.'
                    );
                }

                $folio =
                    $sequence->next_number;

                $sequence->next_number =
                    $folio + 1;

                $sequence->save();

                return $folio;
            },
            attempts: 3
        );
    }
}
