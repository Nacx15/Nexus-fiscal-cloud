<?php

namespace Tests\Unit;

use App\Application\Fiscal\InspectFiscalCredential;
use App\Domain\Fiscal\Exceptions\InvalidFiscalCredential;
use PHPUnit\Framework\TestCase;

class FiscalCredentialInspectorTest extends TestCase
{
    private function fixture(
        string $filename
    ): string {
        $contents = file_get_contents(
            dirname(__DIR__)
            . '/Fixtures/csd/'
            . $filename
        );

        $this->assertNotFalse(
            $contents
        );

        return $contents;
    }

    public function test_valid_csd_can_be_inspected(): void
    {
        $inspector =
            new InspectFiscalCredential();

        $metadata =
            $inspector->execute(
                $this->fixture(
                    'EKU9003173C9.cer'
                ),
                $this->fixture(
                    'EKU9003173C9.key'
                ),
                trim(
                    $this->fixture(
                        'EKU9003173C9.password.txt'
                    )
                ),
                'EKU9003173C9'
            );

        $this->assertSame(
            'EKU9003173C9',
            $metadata->rfc
        );

        $this->assertNotEmpty(
            $metadata->certificateNumber
        );

        $this->assertSame(
            64,
            strlen(
                $metadata->fingerprintSha256
            )
        );

        $this->assertLessThan(
            $metadata->validUntil,
            $metadata->validFrom
        );
    }

    public function test_wrong_private_key_password_is_rejected(): void
    {
        $inspector =
            new InspectFiscalCredential();

        $this->expectException(
            InvalidFiscalCredential::class
        );

        $inspector->execute(
            $this->fixture(
                'EKU9003173C9.cer'
            ),
            $this->fixture(
                'EKU9003173C9.key'
            ),
            'wrong-password',
            'EKU9003173C9'
        );
    }

    public function test_certificate_from_different_rfc_is_rejected(): void
    {
        $inspector =
            new InspectFiscalCredential();

        $this->expectException(
            InvalidFiscalCredential::class
        );

        $inspector->execute(
            $this->fixture(
                'EKU9003173C9.cer'
            ),
            $this->fixture(
                'EKU9003173C9.key'
            ),
            trim(
                $this->fixture(
                    'EKU9003173C9.password.txt'
                )
            ),
            'ABC010101ABC'
        );
    }
}
