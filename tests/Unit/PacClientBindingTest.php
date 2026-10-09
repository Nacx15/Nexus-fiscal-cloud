<?php

namespace Tests\Unit;

use App\Application\Fiscal\Contracts\PacClient;
use App\Infrastructure\Pac\FakePacClient;
use Tests\TestCase;

class PacClientBindingTest extends TestCase
{
    public function test_fake_pac_is_resolved_from_container(): void
    {
        $pac = app(PacClient::class);

        $this->assertInstanceOf(
            FakePacClient::class,
            $pac
        );
    }

    public function test_fake_pac_returns_standard_stamp_result(): void
    {
        $pac = app(PacClient::class);

        $xml =
            '<?xml version="1.0"?>'
            . '<cfdi:Comprobante />';

        $result =
            $pac->stamp($xml);

        $this->assertNotEmpty(
            $result->uuid
        );

        $this->assertSame(
            $xml,
            $result->stampedXml
        );

        $this->assertNotEmpty(
            $result->transactionId
        );
    }
}
