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
            font-size: 16px;
            color: #333;
            margin: 0;
            line-height: 1.1;
        }

        h2 {
            font-size: 14px;
            margin: 2px 0;
        }

        h3 {
            font-size: 16px;
            margin: 3px 0;
        }

        p {
            margin: 1px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
            font-size: 12px;
        }

        th {
            background-color: #ff9900;
            color: white;
            padding: 4px 5px;
            border: 1px solid #ff9900;
            text-align: left;
            font-weight: bold;
            line-height: 1;
        }

        td {
            padding: 4px 5px;
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
            padding: 2px 4px;
            margin: 1px;
            border-radius: 50%;
            font-size: 10px;
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
                    <p style="font-size: 16px; font-weight: bold;"><strong>Agent:</strong> {{ $agent->name }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="report-title">
        <h2>Agent {{ $claimed == 1 ? 'Claimed' : 'Winner' }} Report</h2>

        <p>
            Period:
            {{ Carbon\Carbon::parse($from_date)->format('d M, Y H:i A') }}
            to
            {{ Carbon\Carbon::parse($to_date)->format('d M, Y H:i A') }}
        </p>
    </div>

    @if ($lists)
        <div class="product-section">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Invoice No</th>
                        <th>Product</th>
                        <th>Raffle Ticket</th>
                        <th>Prize({{ $company->currency }})</th>
                        @if ($claimed == 1)
                            <th>Claimed By</th>
                            <th>Claimed Date</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php $rowIndex = 1; @endphp
                    @foreach ($lists as $list)
                        @if (isset($list['tickets']) && count($list['tickets']) > 0)
                            @foreach ($list['tickets'] as $ticketIndex => $ticket)
                                <tr>
                                    @if ($ticketIndex === 0)
                                        <td class="text-center" rowspan="{{ count($list['tickets']) }}">
                                            {{ $rowIndex }}</td>
                                        <td rowspan="{{ count($list['tickets']) }}">
                                            {{ \Carbon\Carbon::parse($list['created_at'])->format('d M, Y H:i A') }}
                                        </td>
                                        <td rowspan="{{ count($list['tickets']) }}">{{ $list['invoice_no'] }}</td>
                                        <td rowspan="{{ count($list['tickets']) }}">{{ $list['product']['title'] }}
                                        </td>
                                    @endif
                                    <td>
                                        @if (isset($ticket['selected_numbers']) && count($ticket['selected_numbers']) > 0)
                                            @foreach ($ticket['selected_numbers'] as $number)
                                                <span class="badge badge-orange">{{ $number }}</span>
                                            @endforeach
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    @if ($ticketIndex === 0)
                                        <td rowspan="{{ count($list['tickets']) }}">
                                            {{ $list['check_win']['total_prize'] ?? '' }}</td>
                                        @if ($claimed == 1)
                                            <td rowspan="{{ count($list['tickets']) }}">
                                                {{ $list['claim_user'] ?? '' }}
                                            </td>
                                            <td rowspan="{{ count($list['tickets']) }}">
                                                {{ \Carbon\Carbon::parse($list['claimed_at'])->format('d M, Y H:i A') }}
                                            </td>
                                        @endif
                                    @endif
                                </tr>
                            @endforeach
                            @php $rowIndex++; @endphp
                        @else
                            <tr>
                                <td class="text-center">{{ $rowIndex }}</td>
                                <td>{{ \Carbon\Carbon::parse($list['created_at'])->format('d M, Y H:i A') }}</td>
                                <td>{{ $list['invoice_no'] }}</td>
                                <td>{{ $list['product']['title'] }}</td>
                                <td>N/A</td>
                                <td>{{ $list['check_win']['total_prize'] ?? '' }}</td>
                                @if ($claimed == 1)
                                    <td>{{ $list['claim_user'] ?? '' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($list['claimed_at'])->format('d M, Y H:i A') }}</td>
                                @endif
                            </tr>
                            </td>
                            </tr>
                            @php $rowIndex++; @endphp
                        @endif
                    @endforeach
                </tbody>
            </table>
            @if (count($lists) > 0)
                @php
                    $totalPrize = 0;
                    foreach ($lists as $list) {
                        $totalPrize += $list['check_win']['total_prize'] ?? 0;
                    }
                @endphp
                <p style="margin-top: 10px; font-weight: bold; font-size: 25px; text-align: right;">
                    <strong>Total:</strong> {{ number_format($totalPrize, 2) }} {{ $company->currency ?? 'AED' }}
                </p>
            @endif
        </div>
    @else
        <p style="text-align:center; margin-top: 20px;">No winning records found for the selected criteria.</p>
    @endif
</body>

</html>
