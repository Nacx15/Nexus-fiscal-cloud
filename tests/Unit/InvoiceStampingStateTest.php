<?php

namespace Tests\Unit;

use App\Domain\Fiscal\Enums\InvoiceStatus;
use App\Models\Invoice;
use LogicException;
use Tests\TestCase;

class InvoiceStampingStateTest extends TestCase
{
    public function test_ready_invoice_can_start_stamping(): void
    {
        $invoice =
            new Invoice([
                'status' =>
                    InvoiceStatus::Ready,

                'stamp_attempts' =>
                    0,
            ]);

        $invoice->markStamping();

        $this->assertSame(
            InvoiceStatus::Stamping,
            $invoice->status
        );

        $this->assertSame(
            1,
            $invoice->stamp_attempts
        );

        $this->assertNotNull(
            $invoice->issued_at
        );

        $this->assertNotNull(
            $invoice
                ->last_stamp_attempt_at
        );
    }

    public function test_stamping_invoice_can_be_marked_as_stamped(): void
    {
        $invoice =
            new Invoice([
                'status' =>
                    InvoiceStatus::Stamping,
            ]);

        $stampedAt =
            now();

        $invoice->markStamped(
            '123e4567-e89b-12d3-a456-426614174000',
            $stampedAt
        );

        $this->assertSame(
            InvoiceStatus::Stamped,
            $invoice->status
        );

        $this->assertSame(
            '123E4567-E89B-12D3-A456-426614174000',
            $invoice->uuid
        );

        $this->assertNotNull(
            $invoice->stamped_at
        );
    }

    public function test_stamping_invoice_can_fail(): void
    {
        $invoice =
            new Invoice([
                'status' =>
                    InvoiceStatus::Stamping,
            ]);

        $invoice->markStampFailed(
            'PAC-001',
            'Temporary PAC error.'
        );

        $this->assertSame(
            InvoiceStatus::StampFailed,
            $invoice->status
        );

        $this->assertSame(
            'PAC-001',
            $invoice->stamp_error_code
        );

        $this->assertSame(
            'Temporary PAC error.',
            $invoice->stamp_error_message
        );
    }

    public function test_failed_invoice_can_be_retried(): void
    {
        $invoice =
            new Invoice([
                'status' =>
                    InvoiceStatus::StampFailed,

                'stamp_attempts' =>
                    1,

                'stamp_error_code' =>
                    'PAC-001',

                'stamp_error_message' =>
                    'Temporary PAC error.',
            ]);

        $invoice->markStamping();

        $this->assertSame(
            InvoiceStatus::Stamping,
            $invoice->status
        );

        $this->assertSame(
            2,
            $invoice->stamp_attempts
        );

        $this->assertNull(
            $invoice->stamp_error_code
        );

        $this->assertNull(
            $invoice->stamp_error_message
        );
    }

    public function test_stamped_invoice_cannot_start_stamping_again(): void
    {
        $invoice =
            new Invoice([
                'status' =>
                    InvoiceStatus::Stamped,

                'stamp_attempts' =>
                    1,
            ]);

        $this->expectException(
            LogicException::class
        );

        $invoice->markStamping();
    }
}
