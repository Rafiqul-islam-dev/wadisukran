<!DOCTYPE html>
<html>
<head>
    <title>Check Wins Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0; font-size: 14px; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-size: 10px; }
        .circle {
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
            border: 1px solid #333;
            border-radius: 50%;
            font-size: 9px;
            margin-right: 1px;
        }
        .row-stack { margin-bottom: 3px; min-height: 18px; line-height: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company->name }}</h1>
        <h2>Check Wins Report</h2>
        <p>From: {{ $from_date }} To: {{ $to_date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>SI</th>
                <th>Date</th>
                <th>Invoice No</th>
                <th>Type</th>
                <th>Description</th>
                <th>Number</th>
                <th>Price (AED)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($wins as $index => $win)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $win->created_at->format('d M Y h:i A') }}</td>
                <td>{{ $win->invoice_no }}</td>
                <td>{{ $win->product->title ?? '' }} {{ $win->product->product_number ?? '' }}</td>
                <td>
                    @foreach($win->check_win['tickets'] as $ticket)
                        <div class="row-stack">{{ $ticket['prize_name'] }}</div>
                    @endforeach
                </td>
                <td>
                    @foreach($win->check_win['tickets'] as $ticket)
                        <div class="row-stack">
                            @foreach($ticket['selected_numbers'] as $num)
                                <span class="circle">{{ $num }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </td>
                <td>AED {{ $win->check_win['total_prize'] }}</td>
                <td>
                    @if($win->is_claimed == 0)
                        Not Claimed
                    @else
                        Claimed
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="background-color: #f2f2f2; font-weight: bold;">
            <tr>
                <td colspan="6" style="text-align: right; border: 1px solid #ddd; padding: 6px;">Total Price (AED):</td>
                <td style="border: 1px solid #ddd; padding: 6px;">AED {{ number_format($totalPrice, 2) }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
