<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BellGas Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            color: #667eea;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 8px 15px 8px 0;
            width: 40%;
        }
        .info-value {
            display: table-cell;
            padding: 8px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #667eea;
        }
        .total-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            border-top: 2px solid #667eea;
            padding-top: 10px;
            margin-top: 10px;
        }
        .payment-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 15px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .info-label, .info-value {
                display: block;
                width: 100%;
            }
            .info-label {
                font-weight: bold;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $receipt['business_info']['name'] ?? 'BellGas' }}</h1>
            <p>Your Official Receipt</p>
            <p>Receipt #{{ $receipt['receipt_info']['receipt_number'] ?? 'N/A' }}</p>
        </div>

        <div class="content">
            <!-- Order Status -->
            <div class="section">
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="payment-status {{ $receipt['payment_details'] ? 'status-paid' : 'status-pending' }}">
                        {{ $receipt['payment_details'] ? 'PAID' : 'PENDING PAYMENT' }}
                    </span>
                </div>
            </div>

            <!-- Order Information -->
            <div class="section">
                <h3>📋 Order Information</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Order Number:</div>
                        <div class="info-value"><strong>{{ $receipt['receipt_info']['order_number'] ?? 'N/A' }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Order Date:</div>
                        <div class="info-value">{{ isset($receipt['order_details']['order_date']) ? date('F j, Y g:i A', strtotime($receipt['order_details']['order_date'])) : 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Fulfillment:</div>
                        <div class="info-value">{{ $receipt['order_details']['fulfillment_method'] ?? 'N/A' }}</div>
                    </div>
                    @if($receipt['payment_details'])
                    <div class="info-row">
                        <div class="info-label">Payment Date:</div>
                        <div class="info-value">{{ isset($receipt['payment_details']['payment_date']) ? date('F j, Y g:i A', strtotime($receipt['payment_details']['payment_date'])) : 'N/A' }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Customer Information -->
            <div class="section">
                <h3>👤 Billing Information</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Name:</div>
                        <div class="info-value">{{ $receipt['customer_info']['name'] ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value">{{ $receipt['customer_info']['email'] ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone:</div>
                        <div class="info-value">{{ $receipt['customer_info']['phone'] ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Delivery Address -->
            @if($receipt['delivery_address'] && $receipt['order_details']['fulfillment_method'] === 'DELIVERY')
            <div class="section">
                <h3>🚚 Delivery Address</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Address Name:</div>
                        <div class="info-value">{{ $receipt['delivery_address']['name'] ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Full Address:</div>
                        <div class="info-value">{{ $receipt['delivery_address']['full_address'] ?? 'N/A' }}</div>
                    </div>
                    @if($receipt['delivery_address']['delivery_instructions'])
                    <div class="info-row">
                        <div class="info-label">Instructions:</div>
                        <div class="info-value">{{ $receipt['delivery_address']['delivery_instructions'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Order Items -->
            <div class="section">
                <h3>🛒 Items Ordered</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($receipt['order_details']['items']))
                            @foreach($receipt['order_details']['items'] as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item['product_name'] ?? 'N/A' }}</strong><br>
                                    <small style="color: #666;">{{ $item['variant_name'] ?? '' }} 
                                    @if(isset($item['weight_kg']))({{ $item['weight_kg'] }}kg)@endif</small>
                                </td>
                                <td>{{ $item['quantity'] ?? 1 }}</td>
                                <td>${{ $item['unit_price'] ?? '0.00' }}</td>
                                <td><strong>${{ $item['total_price'] ?? '0.00' }}</strong></td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">No items available</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Order Total -->
            <div class="section">
                <div class="total-section">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span><strong>${{ $receipt['order_details']['pricing']['subtotal'] ?? '0.00' }}</strong></span>
                    </div>
                    @if(isset($receipt['order_details']['pricing']['shipping_cost']) && $receipt['order_details']['pricing']['shipping_cost'] > 0)
                    <div class="total-row">
                        <span>Shipping & Delivery:</span>
                        <span><strong>${{ $receipt['order_details']['pricing']['shipping_cost'] }}</strong></span>
                    </div>
                    @endif
                    <div class="total-row total-final">
                        <span>Total Paid:</span>
                        <span><strong>${{ $receipt['order_details']['pricing']['total'] ?? '0.00' }} {{ strtoupper($receipt['order_details']['pricing']['currency'] ?? 'AUD') }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            @if($receipt['payment_details'])
            <div class="section">
                <h3>💳 Payment Information</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Payment Status:</div>
                        <div class="info-value"><strong style="color: #28a745;">{{ $receipt['payment_details']['payment_status'] ?? 'SUCCESS' }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Amount Paid:</div>
                        <div class="info-value">${{ $receipt['payment_details']['amount_paid'] ?? '0.00' }} {{ strtoupper($receipt['payment_details']['currency'] ?? 'AUD') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Transaction ID:</div>
                        <div class="info-value"><small>{{ $receipt['payment_details']['payment_intent_id'] ?? 'N/A' }}</small></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Processing:</div>
                        <div class="info-value"><small>{{ $receipt['payment_details']['processing_fee_note'] ?? 'Processed securely by Stripe' }}</small></div>
                    </div>
                </div>

                @if(isset($receipt['stripe_receipt_url']))
                <div style="text-align: center;">
                    <a href="{{ $receipt['stripe_receipt_url'] }}" class="button">
                        View Official Stripe Receipt
                    </a>
                </div>
                @endif
            </div>
            @endif

            <!-- Customer Notes -->
            @if(isset($receipt['order_details']['customer_notes']) && $receipt['order_details']['customer_notes'])
            <div class="section">
                <h3>📝 Order Notes</h3>
                <p style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 0;">
                    {{ $receipt['order_details']['customer_notes'] }}
                </p>
            </div>
            @endif
        </div>

        <div class="footer">
            <p><strong>{{ $receipt['business_info']['name'] ?? 'BellGas' }}</strong></p>
            <p>{{ $receipt['business_info']['address'] ?? 'Sydney, NSW, Australia' }}</p>
            <p>{{ $receipt['business_info']['phone'] ?? '+61 2 1234 5678' }} | <a href="mailto:{{ $receipt['business_info']['email'] ?? 'support@bellgas.com.au' }}">{{ $receipt['business_info']['email'] ?? 'support@bellgas.com.au' }}</a></p>
            
            @if(isset($receipt['business_info']['abn']))
            <p>ABN: {{ $receipt['business_info']['abn'] }}</p>
            @endif
            
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                This receipt was generated on {{ isset($receipt['timestamps']['receipt_generated']) ? date('F j, Y g:i A', strtotime($receipt['timestamps']['receipt_generated'])) : date('F j, Y g:i A') }}
            </p>
        </div>
    </div>
</body>
</html>