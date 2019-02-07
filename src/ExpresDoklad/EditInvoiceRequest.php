<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Entrydo\Domain\Model\Order\Order;
use Entrydo\Domain\Model\Payment\PaymentMethod;

class EditInvoiceRequest
{
    /** @var InvoiceSubscriber */
    private $subscriber;

    /** @var string */
    private $invoiceId;

    /** @var string */
    private $invoiceType;

    /** @var string */
    private $paymentType;

    /** @var \DateTimeImmutable */
    private $exposureDate;

    /** @var \DateTimeImmutable */
    private $maturityDate;

    /** @var InvoiceItem[] */
    private $items;

    /** @var string|null */
    private $variableSymbol;

    /** @var Bank */
    private $bank;

    /** @var string */
    private $language;

    /** @var string */
    private $logoUrl;

    /** @var string */
    private $currencyCode;

    public function __construct(
        string $invoiceId,
        string $language,
        string $logoUrl,
        string $currencyCode,
        string $paymentType,
        ?string $variableSymbol,
        string $invoiceType,
        \DateTimeImmutable $exposureDate,
        \DateTimeImmutable $maturityDate,
        InvoiceSubscriber $subscriber,
        Bank $bank,
        array $items
    ) {
        $this->subscriber = $subscriber;
        $this->invoiceId = $invoiceId;
        $this->invoiceType = $invoiceType;
        $this->paymentType = $paymentType;
        $this->variableSymbol = $variableSymbol;
        $this->exposureDate = $exposureDate;
        $this->maturityDate = $maturityDate;
        $this->bank = $bank;
        $this->language = $language;
        $this->logoUrl = $logoUrl;
        $this->currencyCode = $currencyCode;
        $this->items = array_filter($items, function($item){
            return $item instanceof InvoiceItem;
        });
    }

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public static function createFromOrder(string $invoiceId, string $invoiceType, Order $order, Bank $bank): self
    {
        try {
            if ($order->locale()) {
                $language = $order->locale();
            }
            else {
                $countryCode = $order->customer()->address()->country()->code();
                $language = InvoiceLanguage::byCountryCode($countryCode);
            }
        }
        catch (\Throwable $e) {
            $language = InvoiceLanguage::default;
        }

        $subscriber = InvoiceSubscriber::createFromPerson($order->customer());
        $paymentType = PaymentType::BANK_TRANSFER;

        $payment = $order->getPaidPayment();
        if ($payment) {
            $exposureDate = $payment->paidAt();
            $maturityDate = $payment->paidAt();

            switch ($payment->method()->value()) {
                case PaymentMethod::CREDIT_CARD:
                    $paymentType = PaymentType::CREDIT_CARD;
                    break;

                case PaymentMethod::CASH:
                    $paymentType = PaymentType::CASH;
                    break;
            }
        }
        else {
            $exposureDate = new \DateTimeImmutable();
            $maturityDate = new \DateTimeImmutable('+10 days'); // @TODO this should probably be something more dynamic
        }

        $items = [];
        foreach ($order->getOrderedTickets() as $ticket) {
            $items[] = InvoiceItem::createFromTicket($ticket, 'ks');
        }

        return new self(
            $invoiceId,
            $language,
            ExpresDoklad::LOGO_URL,
            (string) $order->currency()->code(),
            $paymentType,
            $order->variableSymbol(),
            $invoiceType,
            $exposureDate,
            $maturityDate,
            $subscriber,
            $bank,
            $items
        );
    }

    public function getData(): array
    {
        $data = [
            'language' => $this->language,
            'currency_code' => $this->currencyCode,
            'bank' => $this->bank->getData(),
            'subscriber' => $this->subscriber->getData(),
            'items' => [],
        ];

        foreach ($this->items as $item) {
            $data['items'][] = $item->getData();
        }

        $data = array_filter($data, function($item) {
            return $item !== null;
        });

        return $data;
    }
}
