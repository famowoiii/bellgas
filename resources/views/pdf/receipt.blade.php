<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.4;
        }

        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .order-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .order-info-left,
        .order-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .order-info-right {
            text-align: right;
        }

        .info-group {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .customer-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }

        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
        }

        .items-table td {
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #007bff;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .total-label,
        .total-value {
            display: table-cell;
            padding: 3px 0;
        }

        .total-label {
            width: 70%;
            font-weight: normal;
        }

        .total-value {
            width: 30%;
            text-align: right;
        }

        .final-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #333;
            padding-top: 8px;
            margin-top: 8px;
        }

        .payment-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        .terms {
            margin-top: 20px;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $company['name'] }}</div>
            <div class="company-details">
                {{ $company['address'] }}<br>
                Phone: {{ $company['phone'] }} | Email: {{ $company['email'] }}<br>
                ABN: {{ $company['abn'] }} | {{ $company['website'] }}
            </div>
        </div>

        <div class="receipt-title">Tax Invoice / Receipt</div>

        <!-- Order Information -->
        <div class="order-info">
            <div class="order-info-left">
                <div class="info-group">
                    <div class="info-label">Order Number:</div>
                    <div class="info-value">{{ $order->order_number }}</div>
                </div>
                <div class="info-group">
                    <div class="info-label">Order Date:</div>
                    <div class="info-value">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="info-group">
                    <div class="info-label">Payment Method:</div>
                    <div class="info-value">{{ $order->payment_method }}</div>
                </div>
            </div>
            <div class="order-info-right">
                <div class="info-group">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ strtolower($order->status) }}">
                            {{ str_replace('_', ' ', $order->status) }}
                        </span>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Generated:</div>
                    <div class="info-value">{{ $generated_at->format('d/m/Y H:i') }}</div>
                </div>
                @if($order->stripe_payment_intent_id)
                <div class="info-group">
                    <div class="info-label">Payment ID:</div>
                    <div class="info-value">{{ $order->stripe_payment_intent_id }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Customer Information -->
        <div class="customer-info">
            <div class="section-title">Customer Details</div>
            <div class="order-info">
                <div class="order-info-left">
                    <div class="info-group">
                        <div class="info-label">Name:</div>
                        <div class="info-value">{{ $order->user->full_name }}</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Email:</div>
                        <div class="info-value">{{ $order->user->email }}</div>
                    </div>
                    @if($order->user->phone_number)
                    <div class="info-group">
                        <div class="info-label">Phone:</div>
                        <div class="info-value">{{ $order->user->phone_number }}</div>
                    </div>
                    @endif
                </div>
                @if($order->address)
                <div class="order-info-right">
                    <div class="info-group">
                        <div class="info-label">Delivery Address:</div>
                        <div class="info-value">
                            {{ $order->address->full_name }}<br>
                            {{ $order->address->street_address }}<br>
                            @if($order->address->apartment)
                                {{ $order->address->apartment }}<br>
                            @endif
                            {{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postal_code }}<br>
                            {{ $order->address->country }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Order Items -->
        <div class="section-title">Order Items</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%">Product</th>
                    <th style="width: 15%" class="text-center">Variant</th>
                    <th style="width: 10%" class="text-center">Qty</th>
                    <th style="width: 15%" class="text-right">Unit Price</th>
                    <th style="width: 20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->productVariant->product->name }}</strong>
                        @if($item->productVariant->product->description)
                            <br><small style="color: #666;">{{ Str::limit($item->productVariant->product->description, 80) }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->productVariant->variant_name ?? 'Standard' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price_aud, 2) }}</td>
                    <td class="text-right">${{ number_format($item->total_price_aud, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Order Total -->
        <div class="total-section">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-value">${{ number_format($order->subtotal_aud, 2) }}</div>
            </div>
            @if($order->shipping_cost_aud > 0)
            <div class="total-row">
                <div class="total-label">Shipping:</div>
                <div class="total-value">${{ number_format($order->shipping_cost_aud, 2) }}</div>
            </div>
            @endif
            @if($order->tax_amount > 0)
            <div class="total-row">
                <div class="total-label">Tax (GST 10%):</div>
                <div class="total-value">${{ number_format($order->tax_amount, 2) }}</div>
            </div>
            @endif
            <div class="total-row final-total">
                <div class="total-label">Total:</div>
                <div class="total-value">${{ number_format($order->total_aud, 2) }}</div>
            </div>
        </div>

        @if($order->payment_method === 'CARD' && $order->status === 'PAID')
        <!-- Payment Information -->
        <div class="payment-info">
            <div class="section-title">Payment Information</div>
            <div class="info-group">
                <div class="info-label">Payment Status:</div>
                <div class="info-value">✓ Paid via Credit/Debit Card</div>
            </div>
            @if($order->stripe_payment_intent_id)
            <div class="info-group">
                <div class="info-label">Transaction Reference:</div>
                <div class="info-value">{{ $order->stripe_payment_intent_id }}</div>
            </div>
            @endif
        </div>
        @endif

        @if($order->notes)
        <!-- Order Notes -->
        <div class="section-title">Order Notes</div>
        <div style="background: #f8f9fa; padding: 10px; border-radius: 3px; font-style: italic;">
            {{ $order->notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div>Thank you for your business!</div>
            <div class="terms">
                <strong>Terms & Conditions:</strong>
                All sales are final. Returns accepted within 7 days with original receipt.
                For support, contact us at {{ $company['email'] }} or {{ $company['phone'] }}.
            </div>
        </div>
    </div>
</body>
</html>