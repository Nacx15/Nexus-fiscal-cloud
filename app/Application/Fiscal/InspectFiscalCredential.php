<?php

namespace App\Application\Fiscal;

use App\Application\Fiscal\Data\FiscalCredentialMetadata;
use App\Domain\Fiscal\Exceptions\InvalidFiscalCredential;
use PhpCfdi\Credentials\Credential;
use Throwable;

final class InspectFiscalCredential
{
    public function execute(
        string $certificateContents,
        string $privateKeyContents,
        string $privateKeyPassword,
        string $expectedRfc
    ): FiscalCredentialMetadata {
        try {
            $credential = Credential::create(
                $certificateContents,
                $privateKeyContents,
                $privateKeyPassword
            );
        } catch (Throwable $exception) {
            throw new InvalidFiscalCredential(
                'The certificate, private key, or password is invalid.',
                previous: $exception
            );
        }

        if (!$credential->isCsd()) {
            throw new InvalidFiscalCredential(
                'The uploaded certificate is not a CSD.'
            );
        }

        $certificate = $credential->certificate();

        $certificateRfc = strtoupper(
            trim($certificate->rfc())
        );

        $expectedRfc = strtoupper(
            trim($expectedRfc)
        );

        if ($certificateRfc !== $expectedRfc) {
            throw new InvalidFiscalCredential(
                'The certificate RFC does not match the company fiscal profile.'
            );
        }

        $fingerprint = openssl_x509_fingerprint(
            $certificate->pem(),
            'sha256'
        );

        if ($fingerprint === false) {
            throw new InvalidFiscalCredential(
                'Unable to calculate certificate fingerprint.'
            );
        }

        return new FiscalCredentialMetadata(
            certificateNumber:
                $certificate
                    ->serialNumber()
                    ->bytes(),

            rfc:
                $certificateRfc,

            legalName:
                $certificate->legalName(),

            validFrom:
                $certificate
                    ->validFromDateTime(),

            validUntil:
                $certificate
                    ->validToDateTime(),

            fingerprintSha256:
                strtolower(
                    str_replace(
                        ':',
                        '',
                        $fingerprint
                    )
                ),

            currentlyValid:
                $certificate->validOn(),
        );
    }
}
