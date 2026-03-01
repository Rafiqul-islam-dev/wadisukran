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
            padding: 10px;
            background: #fff;
        }

        /* ── Header / Logo ── */
        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        .header img {
            width: 70px;
            height: auto;
        }

        .header h2 {
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
            color: #222;
        }

        /* ── Draw Info ── */
        .draw-info {
            width: 100%;
            margin-bottom: 10px;
        }

        .draw-info tr td {
            padding: 1px 0;
            font-size: 10.5px;
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
            font-size: 12px;
            font-weight: bold;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            padding: 4px 0;
            margin: 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Tax Invoice Table ── */
        .invoice-table {
            width: 100%;
            margin-bottom: 4px;
        }

        .invoice-table tr td {
            padding: 2px 0;
            font-size: 10.5px;
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
            margin: 10px 0;
        }

        /* ── Ticket Details ── */
        .ticket-details-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .ticket-image {
            text-align: center;
            margin-bottom: 8px;
            padding: 10px 0;
        }

        .ticket-image img {
            width: 120px;
            height: auto;
        }

        .numbers-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* ── Number Bubbles ── */
        .numbers-grid {
            text-align: center;
            margin-bottom: 5px;
        }

        .number-bubble {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1.5px solid #333;
            text-align: center;
            line-height: 18px;
            font-size: 10px;
            font-weight: bold;
            margin: 2px 1px;
        }

        /* ── Point of Sale ── */
        .pos-table {
            width: 100%;
            margin-bottom: 6px;
        }

        .pos-table tr td {
            padding: 2px 0;
            font-size: 10.5px;
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
            font-size: 10.5px;
            font-weight: bold;
            margin-top: 4px;
            line-height: 1.5;
        }

        /* ── QR Code ── */
        .qr-section {
            text-align: center;
            margin: 10px 0;
        }

        .qr-section img {
            width: 80px;
            height: 80px;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #ccc;
            padding-top: 8px;
            text-align: center;
        }

        .footer .company-name {
            font-size: 12px;
            font-weight: bold;
        }

        .footer .company-sub {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .footer p {
            font-size: 9.5px;
            color: #444;
            line-height: 1.6;
        }

        .printed-at {
            margin-top: 8px;
            font-size: 9px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="ticket-wrapper">
        {{-- ══ HEADER ══ --}}
        <div class="header">
            <img src="{{ public_path(company_setting()->logo) }}" alt="Logo">
            <h2>{{ $order->product->title }}</h2>
        </div>

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

        {{-- ══ TICKET DETAILS ══ --}}
        <div class="ticket-details-title">Ticket Details</div>

        <div class="ticket-image">
            <img src="{{ public_path($order->product->image) }}" alt="">
        </div>

        <div class="numbers-title">Numbers</div>

        <div class="numbers-grid">
            @foreach ($order->tickets as $ticket)
                <div>
                    @foreach ($ticket->selected_numbers as $number)
                        <span class="number-bubble">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
                    @endforeach
                </div>
            @endforeach
        </div>

        <hr class="divider">

        {{-- ══ POINT OF SALE ══ --}}
        <div class="section-title">Point Of Sales Details</div>

        <table class="pos-table">
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
            <img src="{{ public_path($order->prize) }}" alt="QR Code">
        </div>

        {{-- ══ FOOTER ══ --}}
        <div class="footer">
            <div class="company-name">{{ company_setting()->name }}</div>
            <p>Address: {{ company_setting()->address }}</p>
            <p>Email: {{ company_setting()->email }}</p>
            <p>Website: {{ company_setting()->website }}</p>
        </div>

        <div class="printed-at">
            Printed at: {{ now()->format('d M. Y, h:i A') }}
        </div>

    </div>

</body>

</html>
