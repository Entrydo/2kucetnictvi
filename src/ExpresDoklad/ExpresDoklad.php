<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Doctrine\ORM\EntityManagerInterface;
use Entrydo\Domain\Model\Order\OrderId;
use Entrydo\Domain\Model\Order\OrderRepository;
use Entrydo\Infrastructure\Invoice\BankAccountFactory;
use Entrydo\Infrastructure\Invoice\Invoice;
use Entrydo\Invoicing\OrderInvoiceService;

final class ExpresDoklad implements OrderInvoiceService
{
    public const DEFAULT_LANGUAGE = InvoiceLanguage::EN;

    public const LOGO_URL = 'https://storage.googleapis.com/entrydoapp/images/v-i-c-logo.png';

    // const LOGO_URL = 'https://storage.googleapis.com/entrydoapp/images/logo-grayscale.png';

    /** @var OrderRepository */
    private $orderRepository;

    /** @var Client */
    private $client;

    /** @var BankAccountFactory */
    private $bankAccountFactory;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(OrderRepository $orderRepository, EntityManagerInterface $entityManager, Client $client, BankAccountFactory $bankAccountFactory)
    {
        $this->orderRepository = $orderRepository;
        $this->client = $client;
        $this->bankAccountFactory = $bankAccountFactory;
        $this->entityManager = $entityManager;
    }

    public function getOrderInvoice(OrderId $orderId): Invoice
    {
        $order = $this->orderRepository->orderOfId($orderId);
        $bankAccount = $this->bankAccountFactory->create($order->currency()->code());
        $bank = Bank::createFromBankAccount($bankAccount);

        if ($order->invoiceId() === null) {
            // @TODO this could probable by simplified to be DRY
            if ($order->proformaInvoiceId()) {
                $invoice = $this->markOrderDepositInvoiceAsPaid($orderId);

                $order->setInvoiceId($invoice->invoiceId());
                $this->orderRepository->save($order);
                $this->entityManager->flush();

                return $invoice;
            }

            $request = CreateInvoiceRequest::createFromOrder($order, $bank);
            $invoiceId = $this->client->createInvoice($request);

            $order->setInvoiceId($invoiceId);
            $this->orderRepository->save($order);
            $this->entityManager->flush();
        }
        else {
            $invoiceId = $order->invoiceId();
        }

        $pdf = $this->client->getPdf($invoiceId);

        return new Invoice($invoiceId, $pdf);
    }

    public function getOrderDepositInvoice(OrderId $orderId): Invoice
    {
        $order = $this->orderRepository->orderOfId($orderId);
        $bankAccount = $this->bankAccountFactory->create($order->currency()->code());
        $bank = Bank::createFromBankAccount($bankAccount);

        if ($order->proformaInvoiceId() === null) {
            $request = CreateInvoiceRequest::createFromOrder($order, $bank);
            $invoiceId = $this->client->createInvoice($request);

            $order->setProformaInvoiceId($invoiceId);
            $this->orderRepository->save($order);
            $this->entityManager->flush();
        }
        else {
            $invoiceId = $order->proformaInvoiceId();
        }

        $pdf = $this->client->getPdf($invoiceId);

        return new Invoice($invoiceId, $pdf);
    }

    public function updateOrderInvoices(OrderId $orderId): void
    {
        $order = $this->orderRepository->orderOfId($orderId);
        $bankAccount = $this->bankAccountFactory->create($order->currency()->code());
        $bank = Bank::createFromBankAccount($bankAccount);

        if ($order->invoiceId()) {
            $request = EditInvoiceRequest::createFromOrder($order->invoiceId(), InvoiceType::INVOICE, $order, $bank);
            $this->client->updateInvoice($request);
        }

        if ($order->proformaInvoiceId()) {
            $request = EditInvoiceRequest::createFromOrder($order->proformaInvoiceId(), InvoiceType::DEPOSIT, $order, $bank);
            $this->client->updateInvoice($request);
        }
    }

    private function markOrderDepositInvoiceAsPaid(OrderId $orderId): Invoice
    {
        $order = $this->orderRepository->orderOfId($orderId);
        $request = new ChangeInvoiceStatusRequest($order->proformaInvoiceId(), InvoiceStatus::PAID);
        $createdId = $this->client->changeInvoiceStatus($request);

        $order->setInvoiceId($createdId);
        $this->orderRepository->save($order);
        $this->entityManager->flush();

        $pdf = $this->client->getPdf($createdId);

        return new Invoice($createdId, $pdf);
    }
}
