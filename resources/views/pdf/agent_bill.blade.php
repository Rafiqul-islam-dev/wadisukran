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
            /* DomPDF respects body margin reliably */
            margin: 30px 30px 30px 30px;
        }

        /* ── Logo + Company: TABLE layout (DomPDF-safe) ── */
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

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h2 {
            font-size: 16px;
            margin-bottom: 3px;
        }

        /* ── Vendor info ── */
        .vendor-info {
            margin-bottom: 12px;
        }

        .vendor-info p {
            margin: 3px 0;
        }

        /* ── Main statement table ── */
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
        }

        .main-table th {
            background-color: #eeeeee;
        }

        .col-amount {
            width: 120px;
            text-align: right;
        }

        .bg-gray {
            background-color: #eeeeee;
        }

        .bold {
            font-weight: bold;
        }

        /* ── Footer ── */
        .footer-section {
            margin-top: 18px;
        }

        .footer-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .footer-table th,
        .footer-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            text-align: center;
        }

        .footer-table th {
            background-color: #eeeeee;
        }

        .footer-table td {
            height: 30px;
        }
    </style>
</head>
<body>

    <!-- Company Info: TABLE layout -->
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
                <p><strong>Agent:</strong> {{ $agent['name'] }}</p>
            </td>
        </tr>
    </table>

    <!-- Header -->
    <div class="header">
        <h2>Account Statement</h2>
        <p>Statement Date: <strong>{{ now()->format('d M, Y') }}</strong></p>
    </div>

    <!-- Vendor Info -->
    <div class="vendor-info">
        <p><strong>Vendor Name:</strong> {{ $agent['name'] }}</p>
        <p><strong>Vendor Address:</strong> {{ $agent['address'] }}</p>
    </div>

    <!-- Statement Table -->
    <table class="main-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="col-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Sale</td>
                <td class="col-amount">{{ $agent['total_sell'] }}</td>
            </tr>
            <tr>
                <td><strong>Less:</strong> Commission</td>
                <td class="col-amount">{{ $agent['total_commission'] }}</td>
            </tr>
            <tr>
                <td><strong>Less:</strong> Amount paid for raffle redeem directly by agent</td>
                <td class="col-amount">{{ $agent['total_claim'] }}</td>
            </tr>
            <tr>
                <td><strong>Less:</strong> Agent payment received</td>
                <td class="col-amount">0</td>
            </tr>
            <tr>
                <td><strong>Add:</strong> Prize reimbursement paid to agent</td>
                <td class="col-amount">0</td>
            </tr>
            <tr>
                <td><strong>Less:</strong> Other incentives</td>
                <td class="col-amount">0</td>
            </tr>
            <tr class="bg-gray bold">
                <td>Net Amount</td>
                <td class="col-amount">
                    {{ $agent['total_sell'] - ($agent['total_commission'] + $agent['total_claim']) }}
                </td>
            </tr>
            <tr>
                <td>Old Balance Pending</td>
                <td class="col-amount">{{ $agent['old_due'] }}</td>
            </tr>
            <tr>
                <td>Other charges (If Any)</td>
                <td class="col-amount">0</td>
            </tr>
            <tr class="bg-gray bold">
                <td>Total Due As per Statement</td>
                <td class="col-amount">
                    {{ ($agent['total_sell'] + $agent['old_due']) - ($agent['total_commission'] + $agent['total_claim']) }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer-section">
        <p class="footer-title">To be filled by Representative</p>
        <table class="footer-table">
            <thead>
                <tr>
                    <th>Date of Collection</th>
                    <th>Cash Received</th>
                    <th>No Of Tickets Redeemed</th>
                    <th>Total Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>
