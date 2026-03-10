<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Daily Sales Report</title>
    <style>
        @page {
            margin: 15px 25px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 20px 0;
        }

        h2 {
            font-size: 12px;
            margin: 5px 0;
        }

        p {
            margin: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            font-size: 9px;
        }

        th {
            background-color: #ff9900;
            color: white;
            padding: 6px;
            border: 1px solid #ff9900;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 5px;
            border: 1px solid #ccc;
            text-align: left;
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

        .summary-box {
            display: inline-block;
            border: 1px solid #999;
            padding: 5px;
            margin: 5px 5px 5px 0;
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
            padding: 6px;
            margin-top: 5px;
            margin-bottom: 5px;
            font-weight: bold;
            border-left: 4px solid #ff9900;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #999;
            font-size: 9px;
        }

        .badge {
            display: inline-block;
            padding: 2px 4px;
            margin: 1px;
            border-radius: 50%;
            font-size: 7px;
            font-weight: bold;
        }

        .badge-purple {
            background-color: #e9d5ff;
            color: #7e22ce;
        }

        .badge-orange {
            background-color: #fed7aa;
            color: #ea580c;
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
                </td>
            </tr>
        </table>
    </div>

    <div class="report-title">
        <h2>Daily Sales Report</h2>

        @if (!empty($filters['date_from']))
            <p>
                Period:
                {{ $filters['date_from'] }} {{ $filters['time_from'] ?? '00:00:00' }}
                to
                {{ $filters['date_to'] ?? $filters['date_from'] }} {{ $filters['time_to'] ?? '23:59:59' }}
            </p>
        @endif
    </div>


    @if (count($orders) > 0)
        <table style="font-size: 8px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice</th>
                    <th>Status</th>
                    <th>Vendor</th>
                    <th>Sales Date</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Raffle Ticket</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">VAT</th>
                    <th class="text-right">Commission</th>
                </tr>
            </thead>
            <tbody>
                @php $rowIndex = 1; @endphp
                @foreach ($orders as $order)
                    @if (isset($order->tickets) && count($order->tickets) > 0)
                        @foreach ($order->tickets as $ticketIndex => $ticket)
                            <tr>
                                @if ($ticketIndex === 0)
                                    <td class="text-center" rowspan="{{ count($order->tickets) }}">{{ $rowIndex }}
                                    </td>
                                    <td rowspan="{{ count($order->tickets) }}">{{ $order->invoice_no }}</td>
                                    <td rowspan="{{ count($order->tickets) }}">{{ $order->status ?? 'N/A' }}</td>
                                    <td rowspan="{{ count($order->tickets) }}">
                                        {{ isset($order->user->name) ? $order->user->name : 'N/A' }}</td>
                                    <td rowspan="{{ count($order->tickets) }}">
                                        {{ $order->created_at->format('d M, Y H:i A') }}</td>
                                    <td rowspan="{{ count($order->tickets) }}">
                                        {{ isset($order->product->title) ? $order->product->title : 'N/A' }}</td>
                                @endif
                                <td>
                                    @php $types = (array) $ticket->selected_play_types; @endphp
                                    @foreach ($types as $type)
                                        <span class="badge badge-purple">{{ $type }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if (isset($ticket->selected_numbers) && count($ticket->selected_numbers) > 0)
                                        @foreach ($ticket->selected_numbers as $number)
                                            <span class="badge badge-orange">{{ $number }}</span>
                                        @endforeach
                                    @else
                                        N/A
                                    @endif
                                </td>
                                @if ($ticketIndex === 0)
                                    <td class="text-right" rowspan="{{ count($order->tickets) }}">
                                        {{ number_format($order->total_price, 2) }}
                                        {{ isset($company->currency) ? $company->currency : 'AED' }}</td>
                                    <td class="text-right" rowspan="{{ count($order->tickets) }}">
                                        {{ number_format($order->vat ?? 0, 2) }}
                                        {{ isset($company->currency) ? $company->currency : 'AED' }}</td>
                                    <td class="text-right" rowspan="{{ count($order->tickets) }}">
                                        {{ number_format($order->commission, 2) }}
                                        {{ isset($company->currency) ? $company->currency : 'AED' }}</td>
                                @endif
                            </tr>
                            @php $rowIndex++; @endphp
                        @endforeach
                    @else
                        <tr>
                            <td class="text-center">{{ $rowIndex }}</td>
                            <td>{{ $order->invoice_no }}</td>
                            <td>{{ $order->status ?? 'N/A' }}</td>
                            <td>{{ isset($order->user->name) ? $order->user->name : 'N/A' }}</td>
                            <td>{{ $order->created_at->format('d M, Y H:i A') }}</td>
                            <td>{{ isset($order->product->title) ? $order->product->title : 'N/A' }}</td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td class="text-right">{{ number_format($order->total_price, 2) }}
                                {{ isset($company->currency) ? $company->currency : 'AED' }}</td>
                            <td class="text-right">{{ number_format($order->vat ?? 0, 2) }}
                                {{ isset($company->currency) ? $company->currency : 'AED' }}</td>
                            <td class="text-right">{{ number_format($order->commission, 2) }}
                                {{ isset($company->currency) ? $company->currency : 'AED' }}</td>
                        </tr>
                        @php $rowIndex++; @endphp
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
</body>

</html>
