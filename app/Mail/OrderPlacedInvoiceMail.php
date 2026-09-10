<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "TradeCore Commercial Invoice: Order #{$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h2>Dear {$this->order->buyer->name},</h2>
                <p>Thank you for placing wholesale procurement order <strong>#{$this->order->order_number}</strong>.</p>
                <p>Total Invoice Value: <strong>PKR " . number_format($this->order->total_amount, 2) . "</strong></p>
                <p>Please find the official tax invoice attached as a PDF for your accounts department.</p>
            "
        );
    }

    public function attachments(): array
    {
        $invoiceService = app(InvoiceService::class);
        $pdfContent = $invoiceService->outputRaw($this->order);

        return [
            Attachment::fromData(fn () => $pdfContent, "TradeCore-Invoice-{$this->order->order_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}