<?php

namespace App\Application\Fiscal;

use App\Application\Fiscal\Data\FiscalReadinessResult;
use App\Domain\Fiscal\Enums\FiscalCertificateStatus;
use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Models\Company;
use App\Models\FiscalCertificate;
use App\Models\FiscalSequence;

final class CheckFiscalReadiness
{
    public function execute(
        Company $company
    ): FiscalReadinessResult {
        $profile =
            $company
                ->fiscalProfile()
                ->first();

        $profileReady =
            $profile !== null &&
            $profile->status === 'active';

        $certificate =
            FiscalCertificate::query()
                ->forCompany($company)
                ->where(
                    'status',
                    FiscalCertificateStatus::Active->value
                )
                ->first();

        $certificateReady =
            $certificate !== null &&
            $certificate->isCurrentlyValid();

        $sequence =
            FiscalSequence::query()
                ->forCompany($company)
                ->where(
                    'document_type',
                    FiscalDocumentType::Invoice->value
                )
                ->where(
                    'status',
                    'active'
                )
                ->first();

        $sequenceReady =
            $sequence !== null;

        $checks = [
            'fiscal_profile' => [
                'ready' =>
                    $profileReady,
            ],

            'fiscal_certificate' => [
                'ready' =>
                    $certificateReady,
            ],

            'invoice_sequence' => [
                'ready' =>
                    $sequenceReady,
            ],
        ];

        return new FiscalReadinessResult(
            ready:
                $profileReady &&
                $certificateReady &&
                $sequenceReady,

            checks:
                $checks
        );
    }
}
