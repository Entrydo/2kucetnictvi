<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

class InvoiceStatus
{
    public const CREATED = null;

    public const WAITING = 'waiting';

    public const REFUSED = 'refused';

    public const ACCEPTED = 'accept';

    public const CANCELLED = 'cancel';

    public const PAID = 'payed';
}
