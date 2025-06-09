<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { border: 2px solid #ff0000; border-style: dashed; padding: 15px; margin-bottom: 20px; }
        .logo { max-width: 150px; float: right; }
        .info-box { border: 1px solid #000; padding: 10px; margin: 10px 0; }
        .companies-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .companies-table th, .companies-table td { border: 1px solid #000; padding: 5px; text-align: left; }
        .declaration-box { border: 1px solid #000; padding: 10px; margin: 10px 0; }
        .note { font-size: 0.9em; color: #555; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $logo_path }}" class="logo" alt="HalalLink">
        <h2>SME VERIFICATION REPORT</h2>
    </div>

    <div class="info-box">
        <strong>Report Details:</strong><br>
        <p><strong>Report ID:</strong> {{ $reportValidity->reportValidityID }}</p>
        <p><strong>Validity Period:</strong> {{ $reportValidity->start_timestamp->format('d M Y') }} to {{ $reportValidity->end_timestamp->format('d M Y') }}</p>
        <p><strong>Status:</strong> {{ $reportValidity->approval ? 'Approved' : 'Pending' }}</p>
        <p><strong>Issued By:</strong> {{ $reportValidity->user->fullname }}</p>
    </div>

    <table class="companies-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Company Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach($companies as $index => $company)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $company->company_name }}</td>
                <td>{{ $company->admin ? $company->admin->email : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="declaration-box">
        <p style="text-align: justify;">This is to certify that the above-listed SME companies have been verified by HalalLink and are hereby declared compliant with our standards for the period specified. This verification is valid from {{ $reportValidity->start_timestamp->format('d M Y') }} to {{ $reportValidity->end_timestamp->format('d M Y') }}.</p>
    </div>

    <div class="note">
        <p>This document is computer-generated and does not require a physical signature.</p>
    </div>
</body>
</html>