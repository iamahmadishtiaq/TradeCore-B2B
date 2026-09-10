<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - {{ $order->order_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #2d3748; line-height: 1.4; margin: 0; padding: 25px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 15px; margin-bottom: 25px; }
        .header-title { font-size: 24px; font-weight: bold; color: #4f46e5; text-transform: uppercase; }
        .meta-table, .items-table { width: 100%; border-collapse: collapse; }
        .meta-table td { vertical-align: top; width: 50%; }
        .section-title { font-size: 11px; text-transform: uppercase; font-weight: bold; color: #718096; margin-bottom: 5px; }
        .company-name { font-size: 14px; font-weight: bold; color: #1a202c; }
        .items-table { margin-top: 25px; }
        .items-table th { background-color: #f7fafc; color: #4a5568; font-size: 10px; text-transform: uppercase; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 9px 10px; border-bottom: 1px solid #edf2f7; font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-box { float: right; width: 45%; margin-top: 20px; }
        .total-table { width: 100%; border-collapse: collapse; }
        .total-table td { padding: 6px 0; }
        .grand-total { font-size: 14px; font-weight: bold; color: #4f46e5; border-top: 1px solid #e2e8f0; }
        .footer { position: fixed; bottom: 20px; left: 25px; right: 25px; font-size: 9px; color: #a0aec0; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="header-title">TradeCore B2B</div>
                    <div style="font-size: 10px; color: #718096;">National Commercial Trade & Logistics Network</div>
                </td>
                <td class="text-right">
                    <div style="font-size: 16px; font-weight: bold; color: #1a202c;">COMMERCIAL TAX INVOICE</div>
                    <div><strong>Invoice #:</strong> {{ $order->order_number }}</div>
                    <div><strong>Date:</strong> {{ $order->created_at->format('d M, Y') }}</div>
                    <div><strong>Status:</strong> {{ strtoupper($order->payment_status) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <div class="section-title">Billed & Consigned To:</div>
                <div class="company-name">{{ $order->shipping_address['company'] ?? $order->buyer->name }}</div>
                <div>{{ $order->shipping_address['address'] ?? 'Commercial Location' }}</div>
                <div>{{ $order->shipping_address['city'] ?? '' }}, Pakistan</div>
                <div><strong>NTN/STRN:</strong> {{ $buyerCompany?->tax_number ?? 'B2B-EXEMPT-CORPORATE' }}</div>
                <div><strong>Contact:</strong> {{ $order->shipping_address['contact'] ?? 'N/A' }}</div>
            </td>
            <td class="text-right">
                <div class="section-title">Settlement Terms:</div>
                <div><strong>Payment Mode:</strong> {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</div>
                <div><strong>Fulfillment Mode:</strong> Multi-Vendor Consolidated Consignment</div>
                <div><strong>Platform NTN:</strong> 7482910-4</div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Item Description & Variant</th>
                <th style="width: 20%;">Authorized Vendor</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 10%;">Rate (PKR)</th>
                <th class="text-right" style="width: 10%;">Total (PKR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->variant->product->name }}</strong><br>
                        <span style="font-size: 9px; color: #718096;">SKU: {{ $item->variant->sku }} ({{ $item->variant->title }})</span>
                    </td>
                    <td>{{ $item->vendor->store_name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <table class="total-table">
            <tr>
                <td>Consolidated Subtotal:</td>
                <td class="text-right">PKR {{ number_format($order->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Provincial Sales Tax / GST (5%):</td>
                <td class="text-right">PKR {{ number_format($order->tax_amount, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total Payable:</td>
                <td class="text-right">PKR {{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div style="margin-top: 30px; font-size: 10px; color: #718096;">
        <strong>Logistics Shipments Breakdown:</strong>
        @foreach($order->shipments as $shipment)
            <div>&bull; {{ $shipment->vendor->store_name }} &mdash; Consignment: {{ $shipment->tracking_number ?? 'Pending Allocation' }} (Status: {{ strtoupper($shipment->status) }})</div>
        @endforeach
    </div>

    <div class="footer">
        This is a computer-generated tax invoice issued by TradeCore B2B Enterprise Platform. Valid without signature.
    </div>

</body>
</html>