<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

class InvoiceType
{
    public const DEPOSIT = 'proforma_no_vat';

    public const DEPOSIT_VAT = 'proforma_vat';

    public const INVOICE = 'invoice';
}
