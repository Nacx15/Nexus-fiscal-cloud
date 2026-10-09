<?php

namespace App\Application\Fiscal;

use App\Domain\Fiscal\Enums\FiscalCertificateStatus;
use App\Domain\Fiscal\Exceptions\InvalidFiscalCredential;
use App\Models\FiscalCertificate;
use Illuminate\Support\Facades\DB;

final class ActivateFiscalCertificate
{
    public function execute(
        int $certificateId,
        int $companyId
    ): FiscalCertificate {
        return DB::transaction(
            function () use (
                $certificateId,
                $companyId
            ): FiscalCertificate {

                $certificate =
                    FiscalCertificate::query()
                        ->forCompany($companyId)
                        ->whereKey($certificateId)
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $certificate->certificate_number === null ||
                    $certificate->fingerprint_sha256 === null
                ) {
                    throw new InvalidFiscalCredential(
                        'The fiscal certificate has not been cryptographically validated.'
                    );
                }

                if (
                    !$certificate->isCurrentlyValid()
                ) {
                    throw new InvalidFiscalCredential(
                        'The fiscal certificate is not currently valid.'
                    );
                }

                if (
                    $certificate->status
                    === FiscalCertificateStatus::Revoked
                ) {
                    throw new InvalidFiscalCredential(
                        'A revoked fiscal certificate cannot be activated.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Solo un CSD activo por Company
                |--------------------------------------------------------------------------
                */

                FiscalCertificate::query()
                    ->forCompany($companyId)
                    ->whereKeyNot(
                        $certificate->id
                    )
                    ->where(
                        'status',
                        FiscalCertificateStatus::Active->value
                    )
                    ->update([
                        'status' =>
                            FiscalCertificateStatus::Inactive->value,
                    ]);

                $certificate->status =
                    FiscalCertificateStatus::Active;

                $certificate->save();

                return $certificate->refresh();
            },
            attempts: 3
        );
    }
}
