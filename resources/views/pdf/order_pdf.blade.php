<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Lucky Pencil Ticket</title>
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
            width: 300px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 15px 15px 20px 15px;
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
            background: #111;
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
            margin-bottom: 5px;
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
            line-height: 21px;
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
        <img src="{{ company_setting()->logo_link }}" alt="Company Logo">
        <h2>Lucky Pencil</h2>
    </div>

    {{-- ══ DRAW INFO ══ --}}
    <table class="draw-info">
        <tr>
            <td>Draw Time:</td>
            <td>05.03.23</td>
        </tr>
        <tr>
            <td>Sales Date:</td>
            <td>2025-07-26</td>
        </tr>
        <tr>
            <td>Draw Number:</td>
            <td>101</td>
        </tr>
    </table>

    {{-- ══ TAX INVOICE ══ --}}
    <div class="section-title">Tax Invoice</div>

    <table class="invoice-table">
        <tr>
            <td>TRN:</td>
            <td>104400667900003</td>
        </tr>
        <tr>
            <td>Invoice No:</td>
            <td>260720251595</td>
        </tr>
        <tr>
            <td>Sales Date:</td>
            <td>28 Jul. 2025</td>
        </tr>
        <tr>
            <td>Product Name:</td>
            <td>Lucky Pencil</td>
        </tr>
        <tr>
            <td>Price (incl. VAT):</td>
            <td>5.00</td>
        </tr>
        <tr>
            <td>Quantity:</td>
            <td>3</td>
        </tr>
        <tr>
            <td>Total Amount:</td>
            <td>15.00</td>
        </tr>
    </table>

    <hr class="divider">

    {{-- ══ TICKET DETAILS ══ --}}
    <div class="ticket-details-title">Ticket Details</div>

    <div class="ticket-image">
        <img src="{{ asset('images/lucky_pencil.png') }}" alt="Lucky Pencil">
    </div>

    <div class="numbers-title">Numbers</div>

    <div class="numbers-grid">
        <div>
            <span class="number-bubble">29</span>
            <span class="number-bubble">16</span>
            <span class="number-bubble">35</span>
            <span class="number-bubble">30</span>
            <span class="number-bubble">22</span>
            <span class="number-bubble">21</span>
            <span class="number-bubble">25</span>
        </div>
        <div>
            <span class="number-bubble">32</span>
            <span class="number-bubble">01</span>
            <span class="number-bubble">11</span>
            <span class="number-bubble">63</span>
            <span class="number-bubble">18</span>
            <span class="number-bubble">31</span>
            <span class="number-bubble">26</span>
        </div>
        <div>
            <span class="number-bubble">24</span>
            <span class="number-bubble">02</span>
            <span class="number-bubble">25</span>
            <span class="number-bubble">17</span>
            <span class="number-bubble">26</span>
            <span class="number-bubble">85</span>
            <span class="number-bubble">04</span>
        </div>
    </div>

    <hr class="divider">

    {{-- ══ POINT OF SALE ══ --}}
    <div class="section-title">Point Of Sales Details</div>

    <table class="pos-table">
        <tr>
            <td>Vendor Name:</td>
            <td>zakir</td>
        </tr>
        <tr>
            <td>Address:</td>
            <td>satwa</td>
        </tr>
    </table>

    <div class="prize-note">
        Lucky Pencil Big prize on<br>
        2025-07-26 Amount Of AED<br>
        250000.0
    </div>

    {{-- ══ QR CODE ══ --}}
    <div class="qr-section">
        <img src="{{ asset('images/qr_code.png') }}" alt="QR Code">
    </div>

    {{-- ══ FOOTER ══ --}}
    <div class="footer">
        <div class="company-name">GLAMOUR IMPERIAL</div>
        <div class="company-sub">L.L.C-FZC</div>
        <p>Address: Meydan FZ, Dubai</p>
        <p>Email: infoiwin529@gmail.com</p>
        <p>Website: www.iwin.us.com</p>
    </div>

    <div class="printed-at">
        Printed at: 2025-07-26 20:56
    </div>

</div>

</body>
</html>
