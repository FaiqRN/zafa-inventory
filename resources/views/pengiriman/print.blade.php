<!DOCTYPE html>
<html>
<head>
    <title>Nota Pengiriman - {{ $pengiriman->nomer_pengiriman }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12pt;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: left;
            margin-bottom: 10px;
        }
        
        .nota-info {
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        table, th, td {
            border: 1px solid black;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f0f0f0;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .footer {
            margin-top: 30px;
        }
        
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        
        .signature-box {
            text-align: center;
            width: 45%;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            🖨️ Print Nota
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            ❌ Tutup
        </button>
    </div>

    <div class="header">
        <div>[Tanggal: {{ \Carbon\Carbon::parse($pengiriman->tanggal_pengiriman)->format('d-m-Y') }}]</div>
        <div>Tuan Toko: <strong>{{ $pengiriman->toko->nama_toko }}</strong></div>
        <div style="text-align: right;">[Bayar Segar]</div>
    </div>

    <hr style="border: 1px solid black;">

    <div class="nota-info text-center">
        <strong>NOTA NO. {{ $pengiriman->nomer_pengiriman }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="20%">BANYAKNYA</th>
                <th width="40%">NAMA BARANG</th>
                <th class="text-center" width="20%">HARGA</th>
                <th class="text-center" width="20%">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pengiriman->details as $detail)
            <tr>
                <td class="text-center">{{ $detail->jumlah }} {{ $detail->satuan }}</td>
                <td>{{ $detail->barang->nama_barang }}</td>
                <td class="text-center">{{ number_format($detail->harga, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            @endforeach
            
            @for($i = 0; $i < 3; $i++)
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
        <div style="margin-bottom: 10px;">
            Jumlah Rp. _______________________
        </div>
    </div>

    <div class="signature">
        <div class="signature-box">
            <div>Yang Terima:</div>
            <div style="margin-top: 60px; border-top: 1px solid black; padding-top: 5px;">
                (___________________)
            </div>
        </div>
        <div class="signature-box">
            <div>Hormat kami,</div>
            <div style="margin-top: 60px; border-top: 1px solid black; padding-top: 5px;">
                (___________________)
            </div>
        </div>
    </div>

    <script>
        // Auto print saat halaman dimuat (opsional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
