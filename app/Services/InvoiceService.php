<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoiceService
{
    /**
     * Generate PDF instance for a specific order.
     */
    public function generatePdf(Order $order)
    {
        $order->load(['buyer.companyProfile', 'items.variant.product', 'items.vendor', 'shipments.vendor']);

        return Pdf::loadView('invoices.b2b-tax-invoice', [
            'order' => $order,
            'buyerCompany' => $order->buyer->companyProfile,
        ])->setPaper('a4', 'portrait');
    }

    /**
     * Stream or download PDF directly in browser.
     */
    public function download(Order $order): Response
    {
        $pdf = $this->generatePdf($order);

        return $pdf->download("TradeCore-Invoice-{$order->order_number}.pdf");
    }

    /**
     * Output raw PDF content for email attachment.
     */
    public function outputRaw(Order $order): string
    {
        return $this->generatePdf($order)->output();
    }
}