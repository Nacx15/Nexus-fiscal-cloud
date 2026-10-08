<?php

namespace App\Application\Fiscal;

use App\Models\FiscalCertificate;
use App\Tenancy\CompanyContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class RegisterFiscalCertificate
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function execute(
        UploadedFile $certificateFile,
        UploadedFile $privateKeyFile,
        string $privateKeyPassword
    ): FiscalCertificate {
        $companyId =
            $this->companyContext->id();

        $storage =
            Storage::disk(
                'fiscal_certificates'
            );

        $directory =
            'company-' . $companyId;

        $certificateName =
            Str::uuid()->toString()
            . '.cer';

        $privateKeyName =
            Str::uuid()->toString()
            . '.key';

        $certificatePath =
            $directory
            . '/'
            . $certificateName;

        $privateKeyPath =
            $directory
            . '/'
            . $privateKeyName;

        $storage->putFileAs(
            $directory,
            $certificateFile,
            $certificateName
        );

        try {
            $storage->putFileAs(
                $directory,
                $privateKeyFile,
                $privateKeyName
            );

            return DB::transaction(
                function () use (
                    $companyId,
                    $certificatePath,
                    $privateKeyPath,
                    $privateKeyPassword
                ): FiscalCertificate {
                    $certificate =
                        new FiscalCertificate();

                    $certificate->company_id =
                        $companyId;

                    $certificate
                        ->company_fiscal_profile_id =
                        $this->companyContext
                            ->company()
                            ->fiscalProfile()
                            ->value('id');

                    $certificate->certificate_path =
                        $certificatePath;

                    $certificate->private_key_path =
                        $privateKeyPath;

                    $certificate
                        ->private_key_password =
                        $privateKeyPassword;

                    $certificate->status =
                        'inactive';

                    $certificate->save();

                    return $certificate;
                }
            );
        } catch (Throwable $exception) {
            $storage->delete([
                $certificatePath,
                $privateKeyPath,
            ]);

            throw $exception;
        }
    }
}
