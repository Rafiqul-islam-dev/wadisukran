<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Agent History Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 20px;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #999;
        }

        th, td {
            padding: 8px;
            font-size: 11px;
        }

        th {
            background: #f2f2f2;
            text-align: center;
        }

        td.text-right {
            text-align: right;
        }

        td.text-left {
            text-align: left;
        }

        .footer-total {
            font-weight: bold;
            background: #faf2e8;
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

    </style>
</head>
<body>
    @php
        $grand_total_sell = 0;
        $grand_total_commission = 0;
        $grand_total_win = 0;
        $grand_total_claim = 0;
        $grand_total_posting = 0;
        $grand_total_cancel = 0;
        $grand_old_balance = 0;
        $grand_net_amount = 0;
        $grand_total_due = 0;
    @endphp

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
            </td>
        </tr>
    </table>


    <div class="header">
        <h2>Agent History Report</h2>
        <p>From: {{ $from_date }} To: {{ $to_date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">SL</th>
                <th style="width: 50%;">Agent</th>
                <th>Total Sale</th>
                <th>Commission</th>
                <th>Winning Amount</th>
                <th>Claim Prize</th>
                <th>Posting</th>
                <th>Cancel</th>
                <th>Old Balance</th>
                <th>Net Amount</th>
                <th>Total Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agent_histories as $index => $item)
                @php
                    $grand_total_sell += $item['total_sell'];
                    $grand_total_commission += $item['total_commission'];
                    $grand_total_win += $item['total_win'];
                    $grand_total_claim += $item['total_claim'];
                    $grand_total_posting += $item['total_posting'];
                    $grand_total_cancel += $item['total_cancel'];
                    $grand_old_balance += $item['old_balance'];
                    $grand_net_amount += $item['net_amount'];
                    $grand_total_due += $item['total_due'];
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">
                        {{ $item['agent_name'] }}
                    </td>
                    <td class="text-right">{{ number_format($item['total_sell'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_commission'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_win'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_claim'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_posting'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_cancel'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['old_balance'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['net_amount'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_due'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;">No data found.</td>
                </tr>
            @endforelse
        </tbody>

        @if(count($agent_histories))
            <tfoot>
                <tr class="footer-total">
                    <td colspan="2">Grand Total</td>
                    <td class="text-right">{{ number_format($grand_total_sell, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_commission, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_win, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_claim, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_posting, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_cancel, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_old_balance, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_net_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_due, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>