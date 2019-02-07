<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Entrydo\Domain\Model\Ticket\Ticket;

class InvoiceItem
{
    /** @var string */
    private $name;

    /** @var float */
    private $priceWithVat;

    /** @var int */
    private $amount;

    /** @var string */
    private $unit;

    /** @var int */
    private $vatRate;

    /** @var float */
    private $discount;

    public function __construct(string $name, float $priceWithVat, int $amount, string $unit, int $vatRate, float $discount)
    {
        $this->name = $name;
        $this->priceWithVat = $priceWithVat;
        $this->amount = $amount;
        $this->unit = $unit;
        $this->vatRate = $vatRate;
        $this->discount = $discount;
    }

    public static function createFromTicket(Ticket $ticket, string $unit): self
    {
        $discountValue = $ticket->discount()->isNull() ? 0 : (float) $ticket->discount()->amount()->value();

        $locale = $ticket->getLocale();
        $ticketTranslation = $locale === 'cs' ? 'vstupenka' : 'ticket';
        $variantName = $ticket->variant()->translate($locale)->getName();
        $eventName = $ticket->variant()->event()->translate($locale)->getName();

        $itemName = "${eventName} - ${ticketTranslation} ${variantName}";

        return new self(
            $itemName,
            (float) $ticket->price()->amount()->value(),
            1,
            $unit,
            21,
            $discountValue
        );
    }

    public function getData(): array
    {
        return [
            'name' => $this->name,
            'price_with_vat' => $this->priceWithVat,
            'count' => $this->amount,
            'unit' => $this->unit,
            'vat_rate' => $this->vatRate,
            'discount' => $this->discount,
        ];
    }
}
