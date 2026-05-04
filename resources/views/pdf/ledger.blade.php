<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 30px;
        }
        .company-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .company-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .logo-cell {
            width: 85px;
            padding-right: 10px !important;
        }
        .logo-cell img {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }
        .company-details p {
            margin: 2px 0;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .header h2 {
            font-size: 16px;
            margin-bottom: 3px;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            word-wrap: break-word;
            font-size: 11px;
        }
        .main-table th {
            background-color: #eeeeee;
            font-weight: bold;
        }
        .text-left {
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .sl-col {
            width: 40px;
            text-align: center;
        }
        .amount-col {
            width: 80px;
            text-align: right;
        }
        .date-col {
            width: 120px;
        }
        .payment-type-col {
            width: 150px;
        }
    </style>
</head>
<body>
    <!-- Company Info -->
    <table class="company-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path(company_setting()->logo) }}" alt="Logo">
            </td>
            <td class="company-details">
                <p><strong>{{ company_setting()->name }}</strong></p>
                <p><strong>Address:</strong> {{ company_setting()->address }}</p>
                <p><strong>Email:</strong> {{ company_setting()->email }}</p>
                <p><strong>TRN:</strong> {{ company_setting()->trn_no }}</p>
                @if($agent)
                <p><strong>Agent:</strong> {{ $agent->name }}</p>
                @endif
            </td>
        </tr>
    </table>

    <!-- Header -->
    <div class="header">
        <h2>Account Statement</h2>
        <p>Statement Date: <strong>{{ $from_date }}</strong> to <strong>{{ $to_date }}</strong></p>
    </div>

    <!-- Ledger Table -->
    <table class="main-table">
        <thead>
            <tr>
                <th class="sl-col">SL</th>
                <th class="date-col">Date</th>
                <th class="text-left">Vendor</th>
                <th class="payment-type-col">Payment Type</th>
                <th class="text-left">Description</th>
                <th class="amount-col">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgers as $index => $ledger)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $ledger->created_at ? $ledger->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                <td>{{ $ledger->user->name ?? 'N/A' }}</td>
                <td>{{ $ledger->payment_type == 1 ? 'Agent Payment Received' : 'Agent Payment Received From Company' }}</td>
                <td>{{ $ledger->description ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($ledger->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No records found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
