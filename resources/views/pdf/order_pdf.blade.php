<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="{{ public_path(company_setting()->logo) }}" sizes="any">
    <link rel="icon" href="{{ public_path(company_setting()->logo) }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            background: #fff;
            color: #000;
        }

        .ticket-wrapper {
            margin: 0 auto;
            padding: 5px;
            background: #fff;
        }

        /* ── Header / Logo ── */
        .header-table {
            width: 100%;
            margin-bottom: 2px;
            table-layout: fixed;
            padding: 0 20px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .header-left {
            text-align: left;
            width: 50%;
        }

        .header-left img {
            width: 80px;
            height: auto;
        }

        .header-left h2 {
            font-size: 16px;
            font-weight: bold;
            margin-top: 2px;
            color: #1a2656;
            text-transform: uppercase;
        }

        .header-right {
            text-align: right;
            width: 50%;
        }

        .header-right img {
            width: 80px;
            height: auto;
        }

        /* ── Draw Info ── */
        .draw-info {
            width: 100%;
            margin-bottom: 2px;
        }

        .draw-info tr td {
            padding: 0;
            font-size: 10px;
        }

        .draw-info tr td:first-child {
            color: #555;
            width: 45%;
        }

        .draw-info tr td:last-child {
            font-weight: bold;
        }

        /* ── Section Title ── */
        .section-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Tax Invoice Table ── */
        .invoice-table {
            width: 100%;
            margin-bottom: 1px;
        }

        .invoice-table tr td {
            padding: 0;
            font-size: 10px;
        }

        .invoice-table tr td:first-child {
            color: #555;
            width: 45%;
        }

        .invoice-table tr td:last-child {
            font-weight: bold;
        }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px dashed #aaa;
            margin: 1px 0;
        }

        /* ── Coupon Details ── */
        .coupon-details-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
        }

        .ticket-image {
            text-align: center;
        }

        .ticket-image img {
            width: auto;
            height: 60px;
        }

        .numbers-title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        /* ── Number Bubbles ── */
        .numbers-grid {
            text-align: center;
        }

        .number-bubble {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1px solid #333;
            text-align: center;
            line-height: 14px;
            font-size: 9px;
            font-weight: bold;
            margin: 1px;
        }

        /* ── Play Types ── */
        .play-types {
            width: 100%;
            max-width: 150px;
            margin: 1px auto 2px auto;
            text-align: center;
            font-size: 9px;
            border-collapse: collapse;
        }

        .play-types th {
            font-weight: bold;
            padding: 0 3px 0 3px;
            color: #333;
            line-height: 1;
        }

        .play-types td {
            text-align: center;
            color: #555;
            padding-top: 0;
            line-height: 1;
        }

        .ticket-divider {
            border: none;
            border-top: 1px dashed #ccc;
            margin: 2px auto;
            width: 80%;
        }

        /* ── Point of Sale ── */
        .pos-table {
            width: 100%;
            margin-bottom: 2px;
        }

        .pos-table tr td {
            padding: 0;
            font-size: 10px;
            vertical-align: top;
        }

        .pos-table tr td:first-child {
            color: #555;
            width: 38%;
        }

        .pos-table tr td:last-child {
            font-weight: bold;
        }

        .prize-note {
            font-size: 10px;
            font-weight: bold;
            margin-top: 2px;
            line-height: 1.2;
        }

        /* ── QR Code ── */
        .qr-section {
            text-align: center;
            margin: 4px 0;
        }

        .qr-section img {
            width: 60px;
            height: 60px;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }

        .footer-table {
            width: 100%;
        }

        .footer-logo {
            width: 35%;
            text-align: center;
            vertical-align: top;
        }

        .footer-logo img {
            width: 65px;
            height: auto;
        }

        .footer-info {
            width: 65%;
            vertical-align: top;
            text-align: left;
            padding-left: 10px;
        }

        .footer-info .company-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .info-list {
            font-size: 10.5px;
            color: #222;
        }

        .info-list td {
            padding: 1px 0;
            vertical-align: top;
        }

        .info-list td.label {
            width: 50px;
        }

        .info-list td.colon {
            width: 10px;
            text-align: center;
        }

        .printed-at {
            margin-top: 10px;
            font-size: 10.5px;
            color: #222;
            text-align: left;
        }
    </style>
</head>

<body>

    <div class="ticket-wrapper">
        {{-- ══ HEADER ══ --}}
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <img src="{{ public_path('asset/shukran.png') }}" alt="Shukran Logo">
                    <h2>{{ $order->product->title }}</h2>
                </td>
                <td class="header-right">
                    <img src="{{ public_path('asset/number-'.$order->product->product_number.'.png') }}" alt="Product Number">
                </td>
            </tr>
        </table>

        {{-- ══ DRAW INFO ══ --}}
        <table class="draw-info">
            <tr>
                <td>Draw Time:</td>
                <td>{{ $draw_time }}</td>
            </tr>
            <tr>
                <td>Sales Date:</td>
                <td>{{ $order->created_at }}</td>
            </tr>
            <tr>
                <td>Draw Number:</td>
                <td>{{ $draw_number + 1 }}</td>
            </tr>
        </table>

        {{-- ══ TAX INVOICE ══ --}}
        <div class="section-title">Tax Invoice</div>

        <table class="invoice-table">
            <tr>
                <td>TRN:</td>
                <td>{{ $order->user?->agent?->trn }}</td>
            </tr>
            <tr>
                <td>Invoice No:</td>
                <td>{{ $order->invoice_no }}</td>
            </tr>
            <tr>
                <td>Sales Date:</td>
                <td>{{ $order->created_at->format('d M. Y') }}</td>
            </tr>
            <tr>
                <td>Product Name:</td>
                <td>{{ $order->product->title }}</td>
            </tr>
            <tr>
                <td>Price (incl. VAT):</td>
                <td>{{ $order->product->price }}</td>
            </tr>
            <tr>
                <td>Quantity:</td>
                <td>{{ $order->quantity }}</td>
            </tr>
            <tr>
                <td>Total Amount:</td>
                <td>{{ $order->total_price }}</td>
            </tr>
        </table>

        <hr class="divider">

        {{-- ══ COUPON DETAILS ══ --}}
        <div class="coupon-details-title">Coupon Details</div>
        <hr class="divider">

        <div class="ticket-image">
            <img src="{{ public_path($order->product->image) }}" alt="">
        </div>

        <div class="numbers-title">Numbers</div>

        <div class="numbers-grid">
            @foreach ($order->tickets as $index => $ticket)
                <div>
                    @foreach ($ticket->selected_numbers as $number)
                        <span class="number-bubble">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
                    @endforeach
                </div>

                @if($order->product->prize_type === 'bet')
                    <table class="play-types">
                        <tr>
                            <th>Straight</th>
                            <th>Rumble</th>
                            <th>Chance</th>
                        </tr>
                        <tr>
                            @php 
                                $types = is_array($ticket->selected_play_types) ? $ticket->selected_play_types : [];
                            @endphp
                            <td>{{ in_array('Straight', $types) ? '1' : '0' }}</td>
                            <td>{{ in_array('Rumble', $types) ? '1' : '0' }}</td>
                            <td>{{ in_array('Chance', $types) ? '1' : '0' }}</td>
                        </tr>
                    </table>
                @endif

                @if(!$loop->last)
                    <hr class="ticket-divider">
                @endif
            @endforeach

        </div>

        <hr class="divider">

        {{-- ══ POINT OF SALE ══ --}}
        <div class="section-title">Point Of Sales Details</div>
        <hr class="divider">

        <table class="pos-table">
            <tr>
                <td>Customer Number:</td>
                <td>{{ $order->customer?->phone }}</td>
            </tr>
            <tr>
                <td>Vendor Name:</td>
                <td>{{ $order->user->name }}</td>
            </tr>
            <tr>
                <td>Address:</td>
                <td>{{ $order->user->address }}</td>
            </tr>
        </table>

        <div class="prize-note">
            {{ $order->product->title }} Big prize on
            {{ $order->sales_date }} Amount Of AED
            {{ $order->product->prizes->max('prize') }}
        </div>

        {{-- ══ QR CODE ══ --}}
        <div class="qr-section">
            <img src="{{ public_path($order->qr_code) }}" alt="QR Code">
        </div>

        {{-- ══ FOOTER ══ --}}
        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td class="footer-logo">
                        <img src="{{ public_path(company_setting()->logo) }}" alt="Logo">
                    </td>
                    <td class="footer-info">
                        <div class="company-name">{{ company_setting()->name }}</div>
                        <table class="info-list">
                            <tr>
                                <td class="label">Address</td>
                                <td class="colon">:</td>
                                <td>{{ company_setting()->address }}</td>
                            </tr>
                            <tr>
                                <td class="label">Mobile</td>
                                <td class="colon">:</td>
                                <td>{{ company_setting()->phone }}</td>
                            </tr>
                            <tr>
                                <td class="label">Email</td>
                                <td class="colon">:</td>
                                <td>{{ company_setting()->email }}</td>
                            </tr>
                            <tr>
                                <td class="label">Website</td>
                                <td class="colon">:</td>
                                <td>{{ company_setting()->website }}</td>
                            </tr>
                        </table>
                        <div class="printed-at">
                            Printed at: {{ now()->format('d M. Y, h:i A') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

    </div>

</body>

</html>
