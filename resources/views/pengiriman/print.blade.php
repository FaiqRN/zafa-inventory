<!DOCTYPE html>
<html>
<head>
    <title>Nota Pengiriman - {{ $pengiriman['nomer_pengiriman'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }
        
        .page-container {
            width: 210mm;
            height: 297mm;
            display: flex;
            flex-direction: column;
            gap: 2mm;
            padding: 5mm;
        }
        
        .receipt {
            width: 108mm;
            height: 155mm;
            border: 2px solid black;
            padding: 5mm;
            page-break-inside: avoid;
            position: relative;
        }
        
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .receipt-header-left {
            flex: 1;
        }
        
        .receipt-header-right {
            text-align: right;
            min-width: 40%;
        }
        
        .tanggal {
            font-size: 8pt;
            margin-bottom: 3px;
        }
        
        .toko-owner {
            font-size: 8pt;
            font-weight: bold;
        }
        
        .nota-no {
            font-size: 8pt;
            margin-top: 20px;
        }
        
        .divider {
            border-top: 1px solid black;
            margin: 5px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        
        table, th, td {
            border: 1px solid black;
        }
        
        th, td {
            padding: 3px 4px;
            text-align: left;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .footer {
            position: absolute;
            bottom: 0mm;
            right: 5mm;
            left: 5mm;
        }
        
        .jumlah-rp {
            text-align: right;
            font-size: 8pt;
            margin-bottom: 30px;
        }
        
        .signature {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            margin-bottom: 20px;
        }
        
        .signature-box {
            text-align: center;
            width: 35%;
            height: 80%;
        }
        
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid black;
            padding-top: 3px;
        }
        
        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .receipt {
                border: 2px solid black;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            .page-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            Print Nota
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            Tutup
        </button>
    </div>

    <div class="page-container">
        @for($copy = 1; $copy <= 3; $copy++)
        <div class="receipt">
            <div class="receipt-header">
                <div class="receipt-header-left">
                    <div class="nota-no">
                        Nota No. {{ $pengiriman['nomer_pengiriman'] }}
                    </div>
                </div>
                <div class="receipt-header-right">
                    <div class="tanggal">
                        {{ \Carbon\Carbon::parse($pengiriman['tanggal_pengiriman'])->format('d-m-Y') }}
                    </div>
                    <div class="toko-owner">
                        {{ $pengiriman['toko']->nama_toko }}
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <table>
                <thead>
                    <tr>
                        <th class="text-center" width="22%">BANYAKNYA</th>
                        <th width="38%">NAMA BARANG</th>
                        <th class="text-center" width="20%">HARGA</th>
                        <th class="text-center" width="20%">JUMLAH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pengiriman['items'] as $item)
                    <tr>
                        <td class="text-center">{{ $item['jumlah'] }} {{ $item['satuan'] }}</td>
                        <td>{{ $item['barang']->nama_barang }}</td>
                        <td class="text-right">{{ number_format($item['harga'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    @endforeach
                    
                    @for($i = 0; $i < max(0, 5 - count($pengiriman['items'])); $i++)
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    @endfor
                </tbody>
            </table>

            <div class="footer">
                <div class="jumlah-rp">
                    Jumlah Rp. _________________
                </div>

                <div class="signature">
                    <div class="signature-box">
                        <div>Yang Terima:</div>
                        <div class="signature-line">
                            (____________)
                        </div>
                    </div>
                    <div class="signature-box">
                        <div>Hormat kami,</div>
                        <div class="signature-line">
                            (____________)
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endfor
    </div>
</body>
</html>
