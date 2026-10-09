<?php

namespace App\Application\Fiscal;

use App\Domain\Fiscal\Exceptions\InvalidFiscalCredential;
use App\Models\FiscalCertificate;
use App\Tenancy\CompanyContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class RegisterFiscalCertificate
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly InspectFiscalCredential $inspector
    ) {
    }

    public function execute(
        UploadedFile $certificateFile,
        UploadedFile $privateKeyFile,
        string $privateKeyPassword
    ): FiscalCertificate {
        $companyId =
            $this->companyContext->id();

        $fiscalProfile =
            $this->companyContext
                ->company()
                ->fiscalProfile()
                ->first();

        if ($fiscalProfile === null) {
            throw new InvalidFiscalCredential(
                'The company must have a fiscal profile before registering a CSD.'
            );
        }

        $certificateContents =
            file_get_contents(
                $certificateFile->getRealPath()
            );

        $privateKeyContents =
            file_get_contents(
                $privateKeyFile->getRealPath()
            );

        if (
            $certificateContents === false ||
            $privateKeyContents === false
        ) {
            throw new InvalidFiscalCredential(
                'Unable to read the uploaded credential files.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Cryptographic validation
        |--------------------------------------------------------------------------
        */

        $metadata =
            $this->inspector->execute(
                $certificateContents,
                $privateKeyContents,
                $privateKeyPassword,
                $fiscalProfile->rfc
            );

        /*
        |--------------------------------------------------------------------------
        | Avoid duplicate credential
        |--------------------------------------------------------------------------
        */

        $alreadyExists =
            FiscalCertificate::query()
                ->forCompany($companyId)
                ->where(
                    'fingerprint_sha256',
                    $metadata->fingerprintSha256
                )
                ->exists();

        if ($alreadyExists) {
            throw new InvalidFiscalCredential(
                'This fiscal certificate is already registered.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Private storage
        |--------------------------------------------------------------------------
        */

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
            $storage->putFileAs(
                $directory,
                $certificateFile,
                $certificateName
            );

        if ($certificatePath === false) {
            throw new RuntimeException(
                'Unable to store certificate file.'
            );
        }

        try {
            $privateKeyPath =
                $storage->putFileAs(
                    $directory,
                    $privateKeyFile,
                    $privateKeyName
                );

            if ($privateKeyPath === false) {
                throw new RuntimeException(
                    'Unable to store private key file.'
                );
            }

            return DB::transaction(
                function () use (
                    $companyId,
                    $fiscalProfile,
                    $metadata,
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
                        $fiscalProfile->id;

                    $certificate
                        ->certificate_number =
                        $metadata
                            ->certificateNumber;

                    $certificate
                        ->certificate_path =
                        $certificatePath;

                    $certificate
                        ->private_key_path =
                        $privateKeyPath;

                    $certificate
                        ->private_key_password =
                        $privateKeyPassword;

                    $certificate
                        ->fingerprint_sha256 =
                        $metadata
                            ->fingerprintSha256;

                    $certificate
                        ->valid_from =
                        $metadata->validFrom;

                    $certificate
                        ->valid_until =
                        $metadata->validUntil;

                    $certificate->status =
                        'inactive';

                    $certificate->save();

                    return $certificate;
                }
            );
        } catch (Throwable $exception) {
            $storage->delete([
                $certificatePath,
                $privateKeyPath ?? null,
            ]);

            throw $exception;
        }
    }
}
