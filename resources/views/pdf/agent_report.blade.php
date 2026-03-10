<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Agent Winner Report</title>
    <style>
        @page {
            margin: 15px 25px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            line-height: 1.1;
        }

        h2 {
            font-size: 11px;
            margin: 2px 0;
        }

        h3 {
            font-size: 10px;
            margin: 3px 0;
        }

        p {
            margin: 1px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
            font-size: 8px;
        }

        th {
            background-color: #ff9900;
            color: white;
            padding: 2px 3px;
            border: 1px solid #ff9900;
            text-align: left;
            font-weight: bold;
            line-height: 1;
        }

        td {
            padding: 2px 3px;
            border: 1px solid #ccc;
            text-align: left;
            line-height: 1.1;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        .report-title {
            text-align: center;
            margin: 5px 0;
        }

        .agent-info {
            display: inline-block;
            border: 1px solid #999;
            padding: 3px;
            margin: 2px 5px 2px 0;
            width: 48%;
            vertical-align: top;
        }

        .summary-box {
            display: inline-block;
            border: 1px solid #999;
            padding: 3px;
            margin: 2px 5px 2px 0;
            width: 23%;
            text-align: center;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .section-title {
            background-color: #e0e0e0;
            padding: 3px;
            margin-top: 2px;
            margin-bottom: 2px;
            font-weight: bold;
            border-left: 4px solid #ff9900;
        }

        .footer {
            text-align: center;
            margin-top: 5px;
            padding-top: 3px;
            border-top: 1px solid #999;
            font-size: 8px;
        }

        .badge {
            display: inline-block;
            padding: 1px 2px;
            margin: 0.5px;
            border-radius: 50%;
            font-size: 6px;
            font-weight: bold;
            line-height: 1;
        }

        .badge-purple {
            background-color: #e9d5ff;
            color: #7e22ce;
        }

        .badge-orange {
            background-color: #fed7aa;
            color: #ea580c;
        }

        .badge-claimed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-unclaimed {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .product-section {
            margin-top: 5px;
            margin-bottom: 3px;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width:100%; border:none;">
            <tr>
                <td style="width:90px; border:none;">
                    @if (isset($company->logo))
                        <img src="{{ public_path($company->logo) }}" style="width:80px;">
                    @endif
                </td>

                <td style="border:none;">
                    <h2 style="margin:0;">{{ $company->name }}</h2>

                    <p><strong>Address:</strong> {{ $company->address ?? 'N/A' }}</p>

                    <p><strong>Email:</strong> {{ $company->email ?? 'N/A' }}</p>

                    <p><strong>TRN:</strong> {{ $company->trn_no ?? 'N/A' }}</p>
                    <p><strong>Agent:</strong> {{ $agent->name }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="report-title">
        <h2>Agent Winner Report</h2>

        @if (!empty($filters['from_date']) && !empty($filters['to_date']))
            <p>
                Period:
                {{ $filters['from_date'] }}
                to
                {{ $filters['to_date'] }}
            </p>
        @endif
    </div>

    @if (count($orders) > 0)
        @foreach ($productTotals as $productId => $productData)
            <div class="product-section">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Raffle Ticket</th>
                            <th class="text-right">Prize</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <p style="text-align:center; margin-top: 20px;">No winning records found for the selected criteria.</p>
    @endif

    <div class="footer">
        <p>This report was generated on {{ now()->format('d M, Y H:i A') }}</p>
    </div>
</body>

</html>
