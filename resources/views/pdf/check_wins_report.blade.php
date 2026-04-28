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
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .ticket-tag { background-color: #e1f5fe; color: #01579b; padding: 2px 5px; border-radius: 4px; display: block; margin: 2px; font-size: 10px; }
        .prize-tag { background-color: #bbdefb; color: #0d47a1; padding: 1px 3px; border-radius: 3px; font-size: 9px; }
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
                <th>SL</th>
                <th>Win Date</th>
                <th>Invoice No</th>
                <th>Tickets</th>
                <th>Vendor</th>
                <th>Total Prize</th>
                <th>Product</th>
            </tr>
        </thead>
        <tbody>
            @foreach($wins as $index => $win)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $win->check_win['win_date'] ?? '-' }}</td>
                <td>{{ $win->invoice_no }}</td>
                <td>
                    @foreach($win->check_win['tickets'] as $ticket)
                        <div class="ticket-tag">
                            @foreach($ticket['selected_numbers'] as $num)
                                {{ $num }} 
                            @endforeach
                            <span class="prize-tag">{{ $ticket['prize_name'] }}</span>
                        </div>
                    @endforeach
                </td>
                <td>{{ $win->user->name }}</td>
                <td>{{ $win->check_win['total_prize'] }}</td>
                <td>{{ $win->product->title }} {{ $win->product->product_number }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
