<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Agent History Report</title>

    <style>
        @page {
            margin: 18px 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 14px;
        }

        .header h2 {
            margin: 0;
            font-size: 17px;
            line-height: 1.2;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 10px;
        }

        /* Company section */
        .company-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .company-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .logo-cell {
            width: 75px;
            padding-right: 8px !important;
        }

        .logo-cell img {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }

        .company-details p {
            margin: 2px 0;
            line-height: 1.4;
            font-size: 9px;
        }

        /* Report table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 10px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #999;
            padding: 4px 3px;
            font-size: 9.75px;
            line-height: 1.15;
            vertical-align: middle;
        }

        .report-table th {
            background: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .sl-col {
            width: 4%;
        }

        .date-col {
            width: 25%;
        }

        .amount-col {
            width: 7.88%;
        }
        .footer-total {
            font-weight: bold;
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

        @php
            $selectedAgentName = '';

            if ($selected_agent_id && count($agent_histories)) {
                $firstHistory = collect($agent_histories)->first();
                $selectedAgentName = $firstHistory['agent_name'] ?? '';
            }
        @endphp

        <div class="header">
            <h2>
                Agent History Report
                @if($selectedAgentName)
                    - {{ $selectedAgentName }}
                @endif
            </h2>

            <p>From: {{ $from_date }} To: {{ $to_date }}</p>
        </div>

    <table class="report-table">
        <colgroup>
            <col class="sl-col">

            @if($selected_agent_id)
                <col class="date-col">
            @else
                <col class="date-col">
            @endif

            <col class="amount-col">
            <col class="amount-col">
            <col class="amount-col">
            <col class="amount-col">
            <col class="amount-col">
            <col class="amount-col">
            <col class="amount-col">
            <col class="amount-col">
            <col class="amount-col">
        </colgroup>

        <thead>
            <tr>
                <th>SL</th>

                @if($selected_agent_id)
                    <th>Date</th>
                @else
                    <th>Agent</th>
                @endif

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
                    $grand_total_sell += $item['total_sell'] ?? 0;
                    $grand_total_commission += $item['total_commission'] ?? 0;
                    $grand_total_win += $item['total_win'] ?? 0;
                    $grand_total_claim += $item['total_claim'] ?? 0;
                    $grand_total_posting += $item['total_posting'] ?? 0;
                    $grand_total_cancel += $item['total_cancel'] ?? 0;
                    $grand_old_balance += $item['old_balance'] ?? 0;
                    $grand_net_amount += $item['net_amount'] ?? 0;
                    $grand_total_due += $item['total_due'] ?? 0;
                @endphp

                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>

                    <td class="text-left">
                        @if($selected_agent_id)
                            {{ $item['date_time'] ?? $item['date'] ?? '' }}
                        @else
                            {{ $item['agent_name'] ?? '' }}
                        @endif
                    </td>

                    <td class="text-right">{{ number_format($item['total_sell'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_commission'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_win'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_claim'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_posting'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_cancel'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item['old_balance'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item['net_amount'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item['total_due'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;">No data found.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($agent_histories))
            @php
                $firstItem = collect($agent_histories)->first();
                $lastItem  = collect($agent_histories)->last();

                // View এর মতো:
                // Single agent date-wise হলে Old Balance = first row old_balance
                // Total Due = last row total_due
                // All agent summary হলে সব যোগ হবে
                $footer_old_balance = $selected_agent_id
                    ? ($firstItem['old_balance'] ?? 0)
                    : $grand_old_balance;

                $footer_total_due = $selected_agent_id
                    ? ($lastItem['total_due'] ?? 0)
                    : $grand_total_due;
            @endphp

            <tfoot>
                <tr class="footer-total">
                    <td colspan="2" class="text-center">Total</td>

                    <td class="text-right">{{ number_format($grand_total_sell, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_commission, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_win, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_claim, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_posting, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_cancel, 2) }}</td>
                    <td class="text-right">{{ number_format($footer_old_balance, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_net_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($footer_total_due, 2) }}</td>
                </tr>
            </tfoot>
@endif  
    </table>
</body>
</html>