<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Sales Report</title>
    <style>
        @page {
            margin: 15px 20px 50px 20px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
        }

        th {
            background-color: #ff9900;
            color: white;
            padding: 9px 6px;
            border: 1px solid #444;
            text-align: center;
            font-weight: bold;
        }

        td {
            padding: 7px 6px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }

        /* Removed Yellow Border */
        /* tr.group-start td, tr.group-end td { border-top/bottom yellow border } */

        /* Light horizontal border only for group separation */
        tr.group-start td {
            border-top: 1px solid #bbb;  
        }

        tr:not(.group-start) td {
            border-top: none !important;
        }

        tr:nth-child(even) { 
            background-color: #f9f9f9; 
        }

        .header { 
            border-bottom: 2px solid #333; 
            padding-bottom: 8px; 
            margin-bottom: 12px; 
        }

        .report-title { 
            text-align: center; 
            margin: 12px 0 15px 0; 
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            margin: 1px;
            border-radius: 12px;
            font-size: 7.5px;
            font-weight: bold;
            white-space: nowrap;
        }
        .badge-purple { background-color: #e9d5ff; color: #7e22ce; }
        .badge-orange { background-color: #fed7aa; color: #ea580c; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Table Footer Total */
        .table-footer {
            margin-top: 15px;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            padding: 8px 0;
            border-top: 2px solid #ff9900;
        }

        /* Page Footer */
        .page-footer {
            position: fixed;
            bottom: -35px;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
            color: #333;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin: 0 20px;
        }

        /* PDF Stability */
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        table { page-break-inside: auto; }
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
            <p>Period: {{ $filters['date_from'] }} {{ $filters['time_from'] ?? '00:00' }} 
               to {{ $filters['date_to'] ?? $filters['date_from'] }} {{ $filters['time_to'] ?? '23:59' }}</p>
        @endif
    </div>

    @if (count($orders) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sales Date</th>
                    <th>Invoice No</th>
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
                    @php 
                        $tickets = $order->tickets ?? [];
                        $ticketCount = count($tickets);
                    @endphp
                    
                    @foreach ($tickets as $ticketIndex => $ticket)
                        @php 
                            $isFirst = $ticketIndex === 0;
                            $isLast  = $ticketIndex === $ticketCount - 1;
                        @endphp
                        
                        <tr class="@if($isFirst) group-start @endif">
                            <td class="text-center">@if ($isFirst) {{ $rowIndex }} @endif</td>
                            <td>@if ($isFirst) {{ $order->created_at->format('d M, Y H:i A') }} @endif</td>
                            <td>@if ($isFirst) {{ $order->invoice_no }} @endif</td>
                            <td>@if ($isFirst) {{ $order->product->title ?? 'N/A' }} {{ $order->product->product_number ?? 'N/A' }} @endif</td>

                            <td>
                                @php $types = (array) $ticket->selected_play_types; @endphp
                                @foreach ($types as $type)
                                    <span class="badge badge-purple">{{ $type }}</span>
                                @endforeach
                            </td>

                            <td>
                                @if (!empty($ticket->selected_numbers))
                                    @foreach ($ticket->selected_numbers as $number)
                                        <span class="badge badge-orange">{{ $number }}</span>
                                    @endforeach
                                @else
                                    N/A
                                @endif
                            </td>

                            <td class="text-right">@if ($isFirst) {{ number_format($order->total_price, 2) }} @endif</td>
                            <td class="text-right">@if ($isFirst) {{ number_format($order->vat ?? 0, 2) }} @endif</td>
                            <td class="text-right">@if ($isFirst) {{ number_format($order->commission ?? 0, 2) }} @endif</td>
                        </tr>
                    @endforeach
                    @php $rowIndex++; @endphp
                @endforeach
            </tbody>
        </table>

        <!-- Table Footer Total -->
        <div class="table-footer">
            <strong>Total: {{ number_format($totalPriceSum, 2) }} {{ $company->currency ?? 'AED' }}</strong>
        </div>

    @else
        <p>No records found.</p>
    @endif

    <!-- Page Footer -->
    <div class="page-footer">
        <strong>Total: {{ number_format($totalPriceSum, 2) }} {{ $company->currency ?? 'AED' }}</strong>
    </div>
</body>
</html>