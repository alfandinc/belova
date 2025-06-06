<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        .header img {
            max-height: 60px;
        }
        .logo-title {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
        }
        .invoice-details, .patient-details {
            width: 48%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f8f8;
        }
        .total-row td {
            font-weight: bold;
            border-top: 2px solid #000;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .signature {
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-title">
            <div>
                <img src="{{ public_path('img/logo.png') }}" alt="Belova Logo">
            </div>
            <div>
                <h1 style="margin: 0; font-size: 24px;">INVOICE</h1>
                <p style="margin: 5px 0 0;">{{ $invoice->invoice_number }}</p>
            </div>
        </div>
        
        <div class="invoice-info">
            <div class="invoice-details">
                <h3>Invoice To:</h3>
                <p>
                    <strong>{{ $invoice->visitation->pasien->nama ?? 'N/A' }}</strong><br>
                    No. RM: {{ $invoice->visitation->pasien->no_rm ?? 'N/A' }}<br>
                    {{ $invoice->visitation->pasien->alamat ?? 'N/A' }}
                </p>
            </div>
            <div class="patient-details">
                <table style="border: none;">
                    <tr>
                        <td style="border: none; padding: 3px 0;">Invoice Date</td>
                        <td style="border: none; padding: 3px 0;">: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 3px 0;">Visit Date</td>
                        <td style="border: none; padding: 3px 0;">: {{ \Carbon\Carbon::parse($invoice->visitation->tanggal_visitation)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 3px 0;">Clinic</td>
                        <td style="border: none; padding: 3px 0;">: {{ $invoice->visitation->klinik->nama ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 30%;">Item</th>
                <th style="width: 25%;">Description</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 12%;">Price</th>
                <th style="width: 10%;">Discount</th>
                <th style="width: 10%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">
                    @if($item->discount > 0)
                        @if($item->discount_type == '%')
                            {{ $item->discount }}%
                        @else
                            Rp {{ number_format($item->discount, 0, ',', '.') }}
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">Rp {{ number_format($item->final_amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right"><strong>Total:</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div style="margin-top: 30px;">
        <h4>Payment Information:</h4>
        <p>
            Please make payment to the following account:<br>
            Bank: BCA<br>
            Account Name: Belova Clinic<br>
            Account Number: 123-456-7890<br>
        </p>
    </div>
    
    <div class="signature">
        <div class="text-center" style="float: right; width: 200px;">
            <p>Tangerang, {{ \Carbon\Carbon::now()->format('d F Y') }}</p>
            <br><br><br>
            <p>_________________________</p>
            <p>Petugas Keuangan</p>
        </div>
    </div>
    
    <div class="footer">
        <p class="text-center">Thank you for choosing Belova Clinic</p>
        <p class="text-center">For questions regarding this invoice, please contact our finance department at finance@belovaclinic.com</p>
    </div>
</body>
</html>