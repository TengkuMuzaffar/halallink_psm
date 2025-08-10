<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { border: 2px solid #ff0000; border-style: dashed; padding: 15px; margin-bottom: 20px; }
        .logo { max-width: 150px; float: right; }
        .customer-info { margin-bottom: 20px; }
        .order-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .order-info-item { width: 30%; }
        .order-details { margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 5px; text-align: left; }
        .items-table th { background-color: #f2f2f2; }
        .subtotal-section { width: 100%; text-align: right; margin-top: 20px; }
        .total-section { width: 100%; text-align: right; margin-top: 10px; font-weight: bold; }
        .note { font-size: 0.9em; color: #555; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $logo_path }}" class="logo" alt="HalalLink">
        <h2>Order Receipt</h2>
    </div>

    <div class="customer-info">
        <strong>Customer Name:</strong> {{ $order->user->fullname ?? $order->user->email }}<br>
        <strong>Shipping Address:</strong> {{ $order->location->company_address ?? 'N/A' }}<br>
    </div>

    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td><strong>Order ID</strong></td>
            <td><strong>Order Date</strong></td>
            <td><strong>Payment Date</strong></td>
            <td><strong>Payment Method</strong></td>
        </tr>
        <tr>
            <td>{{ $order->orderID }}</td>
            <td>{{ $order->created_at->format('d/m/y H:i:s') }}</td>
            <td>{{ $order->payment->payment_timestamp ? $order->payment->payment_timestamp->format('d/m/y H:i:s') : 'N/A' }}</td>
            <td>ToyyibPay</td>
        </tr>
    </table>

    <h3>Order Details</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Product</th>
                <th>Variation</th>
                <th>Product Price</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->cartItems as $index => $cartItem)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $cartItem->item->poultry->poultry_name ?? 'Unknown Product' }}</td>
                <td>{{ $cartItem->item->measurement_value }} {{ $cartItem->item->measurement_type }}</td>
                <td>RM{{ number_format($cartItem->price_at_purchase, 2) }}</td>
                <td>{{ $cartItem->quantity }}</td>
                <td>RM{{ number_format($cartItem->price_at_purchase * $cartItem->quantity, 2) }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="5" style="text-align: right;"><strong>Payment Gateway Fee (ToyyibPay)</strong></td>
                <td>RM1.00</td>
            </tr>
        </tbody>
    </table>

    <div class="subtotal-section">
        <strong>Subtotal</strong> RM{{ number_format($subtotal, 2) }}<br>
        <strong>Total Quantity</strong> {{ $total_quantity }} item(s)
    </div>

    <div class="total-section">
        <h3>Total Payment</h3>
        <h2>RM{{ number_format($subtotal + 1, 2) }}</h2>
    </div>

    <div class="note" style="margin-top: 40px;">
        <p><strong>Note:</strong> The HalalLink Order Receipt is not a tax invoice.</p>
        <p>An additional RM 1.00 fee is charged for using the ToyyibPay payment gateway.</p>
        <p>For products sold by third-party sellers on HalalLink, Goods & Services Tax (GST) may be applicable. Not all third-party sellers will be required to charge GST as they may not meet the requirements for GST registration.</p>
        <p>HalalLink cannot provide a GST breakdown or tax invoice on behalf of third-party sellers. Please contact the seller directly for questions about GST or if you require a GST breakdown or tax invoice.</p>
        <p>End of Receipt</p>
    </div>
</body>
</html>