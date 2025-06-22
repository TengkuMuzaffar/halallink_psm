<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { border: 2px solid #ff0000; border-style: dashed; padding: 15px; margin-bottom: 20px; }
        .logo { max-width: 150px; float: right; }
        .shipper-box { border: 1px solid #000; padding: 10px; margin: 10px 0; }
        .consignee-box { border: 1px solid #000; padding: 10px; margin: 10px 0; }
        .transport-details { border: 1px solid #000; padding: 10px; margin: 10px 0; }
        .goods-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .goods-table th, .goods-table td { border: 1px solid #000; padding: 5px; text-align: left; }
        .warning-box { border: 1px solid #000; padding: 10px; margin: 10px 0; background-color: #f8f8f8; }
        .declaration-box { border: 1px solid #000; padding: 10px; margin: 10px 0; }
        .emergency-contact { border: 1px solid #000; padding: 10px; margin: 10px 0; }
        .note { font-size: 0.9em; color: #555; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $logo_path }}" class="logo" alt="HalalLink">
        <h2>DELIVERY FOR POULTRY SHIPMENT</h2>
    </div>

    <div class="shipper-box">
        <strong>Shipper:</strong><br>
        {{ $source->company->company_name }}<br>
        {{ $source->company_address }}<br>
        Phone: {{ $source->company->admin->tel_number }}
    </div>

    <div class="consignee-box">
        <strong>Consignee:</strong><br>
        {{ $destination->company->company_name }}<br>
        {{ $destination->company_address }}<br>
        Phone: {{ $destination->company->admin->tel_number }}
    </div>

    <table class="goods-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Item Name</th>
                <th>Measurement</th>
                <th>Quantity</th>
                <th>OrderID</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $cart->cartID }}</td>
                <td>{{ $cart->item->poultry->poultry_name }}</td>
                <td>{{ $cart->item->measurement_value }}{{ $cart->item->measurement_type }}</td>
                <td>{{ $cart->quantity }}</td>
                <td>{{ $cart->order->orderID }}</td>
            </tr>
        </tbody>
    </table>

    <div class="emergency-contact">
        <strong>24-HOUR EMERGENCY CONTACT:</strong><br>
        Phone: {{ $source->company->admin->tel_number }}
    </div>

    <div class="declaration-box">
        <p style="text-align: justify;">I hereby declare that the contents of this consignment are fully and accurately described above by the proper shipping name, and are classified, packaged, marked and labeled/placarded, and are in all respects in proper condition for transport according to applicable international and national governmental regulations.</p>
    </div>

    <div class="note">
        <p>This document is computer-generated and does not require a physical signature.</p>
    </div>
</body>
</html>