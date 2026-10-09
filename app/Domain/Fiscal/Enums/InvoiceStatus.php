<?php

namespace App\Domain\Fiscal\Enums;

enum InvoiceStatus: string
{
    case Ready = 'ready';

    case Stamping = 'stamping';

    case Stamped = 'stamped';

    case StampFailed = 'stamp_failed';

    case CancelPending = 'cancel_pending';

    case Cancelled = 'cancelled';

    case CancelFailed = 'cancel_failed';
}
