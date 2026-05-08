<!DOCTYPE html>
<html>
<head>
    <title>Probable Wins Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0; font-size: 20px; }
        .company-info { margin-bottom: 10px; }
        .company-info p { margin: 2px 0; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-size: 10px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 10px 0; background-color: #f9f9f9; padding: 8px; border-left: 4px solid #4a5568; }
        .vendor-section { margin-top: 20px; }
        .vendor-header { background-color: #f97316; color: white; font-weight: bold; }
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
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-straight { background-color: #d1fae5; color: #065f46; }
        .badge-rumble { background-color: #dbeafe; color: #1e40af; }
        .badge-chance { background-color: #e9d5ff; color: #6b21a8; }
        .badge-number { background-color: #c7d2fe; color: #3730a3; }
    </style>
</head>
@php
    $headingTitle = '';
    if ($pickNumbers) {
        $headingTitle = "Prize Summary For Number {$pickNumbers}";
        if ($matchTypeLabel) {
            $headingTitle .= " ({$matchTypeLabel})";
        }
    }
@endphp

<body>
    <div class="header">
        <h1>{{ $company->name }}</h1>
        <p>{{ $company->address }}</p>
        <p>Email: {{ $company->email }} | TRN: {{ $company->trn_no }}</p>
        <h2>Probable Winner Statement</h2>
        <p>Date Range: {{ $from_date }} To: {{ $to_date }}</p>
        @if($product)
        <p>Product: {{ $product->title }} {{ $product->product_number }}</p>
        @endif
    </div>

    <!-- Summary Table View -->
    <div class="section-title">{{ $headingTitle ?: 'Probable Winner Statement' }}<</div>
    <table>
        <thead>
            <tr>
                <th>Match Type</th>
                <th>Winners</th>
                <th>Prize Per Winner</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $win)
            <tr>
                <td>{{ $win['match_type'] }}</td>
                <td>{{ $win['winners'] }}</td>
                <td>{{ $win['prize_per_winner'] }} {{ $company->currency }}</td>
                <td>{{ $win['total_amount'] }} {{ $company->currency }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="background-color: #f2f2f2; font-weight: bold;">
            <tr>
                <td colspan="3" style="text-align: right; border: 1px solid #ddd; padding: 6px;">Grand Total:</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ number_format($totalAmount, 2) }} {{ $company->currency }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Vendor List -->
    <div class="section-title vendor-section">Winner Vendors ({{ count($groupedOrders) }} Winners)</div>
    <table>
        <thead>
            <tr class="vendor-header" style="color:#000;">
                <th>#</th>
                <th>Product</th>
                <th>Invoice No</th>
                <th>Date</th>
                <th>Type</th>
                <th>Vendor</th>
                <th>Raffle Ticket</th>
                <th>Match Type</th>
                <th>Win Amount</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedOrders as $index => $group)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $group['product_name'] }}</td>
                <td>{{ $group['invoice_no'] }}</td>
                <td width="70px">{{ $group['created_at'] }}</td>
                <td>
                    @foreach($group['items'] as $item)
                        @php
                            $type = preg_replace('/\s+\d+$/', '', $item['match_type'] ?? '');
                            
                        @endphp
                        <div class="row-stack">
                            <span >{{ $type }}</span>
                        </div>
                    @endforeach
                </td>
                <td>
                    {{ $group['vendor_name'] }}
                </td>
                <td style="text-align: center; white-space: nowrap;">
                    @foreach($group['items'] as $item)
                        <div class="row-stack">
                            @foreach($item['selected_numbers'] as $num)
                                <span class="circle">{{ $num }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </td>
                <td>
                    @foreach($group['items'] as $item)
                        @php
                            $type = preg_replace('/\s+\d+$/', '', $item['match_type'] ?? '');
                           
                        @endphp
                        <div class="row-stack">
                            <span >{{ $item['match_type'] ?? '' }}</span>
                        </div>
                    @endforeach
                </td>
                <td>
                    @foreach($group['items'] as $item)
                        <div class="row-stack">{{ $item['win_amount'] }} {{ $company->currency }}</div>
                    @endforeach
                </td>
                <td>{{ $group['total_win_amount'] }} {{ $company->currency }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="background-color: #f2f2f2; font-weight: bold;">
            <tr>
                <td colspan="9" style="text-align: right; border: 1px solid #ddd; padding: 6px;">Total Win Amount:</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ number_format($totalWinAmount, 2) }} {{ $company->currency }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
