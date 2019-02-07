<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Entrydo\Domain\Model\Order\Order;
use Entrydo\Domain\Model\Payment\PaymentMethod;

class CreateInvoiceRequest
{
    /** @var InvoiceSubscriber */
    private $subscriber;

    /** @var Bank */
    private $bank;

    /** @var InvoiceItem[] */
    private $items;

    /** @var string */
    private $language;

    /** @var string */
    private $logoUrl;

    /** @var string */
    private $currencyCode;

    /** @var string */
    private $paymentType;

    /** @var string|null */
    private $variableSymbol;

    /** @var string */
    private $invoiceType;

    /** @var \DateTimeImmutable */
    private $exposureDate;

    /** @var \DateTimeImmutable */
    private $maturityDate;

    /** @var string|null */
    private $status;

    public function __construct(
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
        array $items,
        ?string $status
    ) {
        $this->subscriber = $subscriber;
        $this->bank = $bank;
        $this->language = $language;
        $this->logoUrl = $logoUrl;
        $this->currencyCode = $currencyCode;
        $this->paymentType = $paymentType;
        $this->variableSymbol = $variableSymbol;
        $this->invoiceType = $invoiceType;
        $this->exposureDate = $exposureDate;
        $this->maturityDate = $maturityDate;
        $this->items = array_filter($items, function($item){
            return $item instanceof InvoiceItem;
        });
        $this->status = $status;
    }

    public static function createFromOrder(Order $order, Bank $bank): self
    {
        if ($order->locale()) {
            $language = InvoiceLanguage::byValue($order->locale());
        }
        else {
            $language = InvoiceLanguage::byCountryCode($order->customer()->address()->country()->code());
        }

        $subscriber = InvoiceSubscriber::createFromPerson($order->customer());
        $paymentType = PaymentType::BANK_TRANSFER;

        $payment = $order->getPaidPayment();
        if ($payment && $order->isPaid()) {
            $invoiceType = InvoiceType::INVOICE;
            $invoiceStatus = InvoiceStatus::PAID;
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
            $invoiceType = InvoiceType::DEPOSIT;
            $invoiceStatus = InvoiceStatus::CREATED;
            $exposureDate = new \DateTimeImmutable();
            $maturityDate = new \DateTimeImmutable('+10 days'); // @TODO this should probably be something more dynamic
        }

        $items = [];
        foreach ($order->getOrderedTickets() as $ticket) {
            $items[] = InvoiceItem::createFromTicket($ticket, 'ks');
        }

        return new self(
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
            $items,
            $invoiceStatus
        );
    }

    public function getData(): array
    {
        $data = [
            'language' => $this->language,
            'logo_url' => $this->logoUrl,
            'currency_code' => $this->currencyCode,
            'payment_type' => $this->paymentType,
            'variable_symbol' => $this->variableSymbol,
            'invoice_type' => $this->invoiceType,
            'exposure_datetime' => $this->exposureDate->format('Y-m-d'),
            'maturity_datetime' => $this->maturityDate->format('Y-m-d'),
            'bank' => $this->bank->getData(),
            'subscriber' => $this->subscriber->getData(),
            'items' => [],
            'status' => $this->status,
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
