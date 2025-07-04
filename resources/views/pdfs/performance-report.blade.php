<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Performance Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 25px; }
        .section h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .metric-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .metric-title { font-weight: bold; color: #555; }
        .metric-value { font-size: 1.2em; color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .footer { margin-top: 30px; text-align: center; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HalalLink Performance Report</h1>
        <p>Generated on: {{ $generated_at }}</p>
        <p>Generated by: {{ $generated_by }}</p>
    </div>

    <div class="section">
        <h2>Executive Summary</h2>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">Total Companies</div>
                <div class="metric-value">{{ $overview['total_companies'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Total Users</div>
                <div class="metric-value">{{ $overview['total_users'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Total Orders</div>
                <div class="metric-value">{{ $overview['total_orders'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Total Revenue</div>
                <div class="metric-value">RM {{ number_format($overview['total_revenue'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Top Performers</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Company Name</th>
                    <th>Type</th>
                    <th>Performance Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_performers as $index => $performer)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $performer['company']['name'] }}</td>
                    <td>{{ ucfirst($performer['company']['type']) }}</td>
                    <td>{{ $performer['overall_score'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Industry Benchmarks</h2>
        @foreach($industry_benchmarks as $type => $benchmark)
        <h3>{{ ucfirst($type) }} Industry</h3>
        <table>
            <tr>
                <td>Average Order Success Rate</td>
                <td>{{ $benchmark['avg_order_success_rate'] }}%</td>
            </tr>
            <tr>
                <td>Average Delivery Success Rate</td>
                <td>{{ $benchmark['avg_delivery_success_rate'] }}%</td>
            </tr>
            <tr>
                <td>Average Verification Success Rate</td>
                <td>{{ $benchmark['avg_verification_success_rate'] }}%</td>
            </tr>
            <tr>
                <td>Average Payment Success Rate</td>
                <td>{{ $benchmark['avg_payment_success_rate'] }}%</td>
            </tr>
        </table>
        @endforeach
    </div>

    <div class="footer">
        <p>This report is confidential and intended for authorized personnel only.</p>
        <p>© {{ date('Y') }} HalalLink. All rights reserved.</p>
    </div>
</body>
</html>