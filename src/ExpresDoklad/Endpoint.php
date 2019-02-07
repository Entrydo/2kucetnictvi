<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

class Endpoint
{
    public const TOKEN_URL = '/oauth/token';

    public static function createInvoice(string $companyId): string
    {
        return "/rest-api/${companyId}/issued-invoices";
    }

    public static function editInvoice(string $companyId, string $invoiceId): string
    {
        return "/rest-api/${companyId}/issued-invoices/${invoiceId}";
    }

    public static function getPdf(string $companyId, string $invoiceId): string
    {
        return "/rest-api/${companyId}/issued-invoices/${invoiceId}/pdf";
    }

    public static function changeInvoiceStatus(string $companyId, string $invoiceId, ?string $status): string
    {
        return "/rest-api/${companyId}/issued-invoices/${invoiceId}/status" . ($status ? "?status=${status}" : '');
    }
}
