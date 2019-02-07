<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

class PaymentType
{
    public const BANK_TRANSFER = 'bank_transfer';

    public const CASH = 'cash';

    public const CASH_ON_DELIVERY = 'cash_on_delivery';

    public const CREDIT_CARD = 'credit_card';

    public const PAYPAL = 'pay_pal';
}
