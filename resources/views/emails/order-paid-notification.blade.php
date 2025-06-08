<!DOCTYPE html>
<html>
<head>
    <title>Order Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #123524;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .order-summary {
            background-color: #f2f2f2;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>New Order Notification</h2>
    </div>
    
    <div class="content">
        <p>Hello {{ $companyItems['company_name'] }},</p>
        
        <p>A new order has been placed and successfully paid that includes items from your inventory. Please prepare the following items for processing:</p>
        
        <div class="order-summary">
            <p><strong>Order ID:</strong> #{{ $order->orderID }}</p>
            <p><strong>Order Date:</strong> {{ $order->order_timestamp->format('d M Y, h:i A') }}</p>
            <p><strong>Customer:</strong> {{ $buyer->fullname }}</p>
        </div>
        
        <h3>Items to Prepare:</h3>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($companyItems['items'] as $item)
                <tr>
                    <td>{{ $item['item_name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>RM {{ number_format($item['price'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <p>Please ensure these items are ready for delivery or pickup as scheduled.</p>
        
        <p>Thank you for your prompt attention to this order.</p>
        
        <p>Best regards,<br>HalalLink Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message from HalalLink. Please do not reply to this email.</p>
    </div>
</body>
</html>