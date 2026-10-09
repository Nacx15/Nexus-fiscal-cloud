<?php

namespace App\Application\Fiscal;

use App\Models\FiscalSequence;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class AllocateFiscalFolio
{
    public function execute(
        int $sequenceId,
        int $companyId
    ): int {
        return DB::transaction(
            fn () =>
                $this->executeWithinTransaction(
                    $sequenceId,
                    $companyId
                ),
            attempts: 3
        );
    }

    public function executeWithinTransaction(
        int $sequenceId,
        int $companyId
    ): int {
        if (DB::transactionLevel() === 0) {
            throw new LogicException(
                'Fiscal folio allocation requires an active database transaction.'
            );
        }

        $sequence =
            FiscalSequence::query()
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
    }
}
