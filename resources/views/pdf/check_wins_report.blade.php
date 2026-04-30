<!DOCTYPE html>
<html>
<head>
    <title>Check Wins Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        .circle { 
            display: inline-block; 
            width: 20px; 
            height: 20px; 
            line-height: 20px; 
            text-align: center; 
            border: 1px solid #333; 
            border-radius: 50%; 
            font-size: 10px; 
            margin-right: 2px;
        }
        .row-stack { margin-bottom: 5px; height: 22px; line-height: 22px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company->name }}</h1>
        <h2>Check Wins (Claimed Winners) Report</h2>
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
    </table>
</body>
</html>
