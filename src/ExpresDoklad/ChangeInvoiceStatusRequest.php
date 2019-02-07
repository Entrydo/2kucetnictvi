<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

class ChangeInvoiceStatusRequest
{
    /** @var string */
    private $invoiceId;

    /** @var string|null */
    private $status;

    public function __construct(string $invoiceId, ?string $status)
    {
        $this->invoiceId = $invoiceId;
        $this->status = $status;
    }

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
