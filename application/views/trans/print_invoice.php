<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= str_pad($tagihan->id_tagihan, 6, '0', STR_PAD_LEFT) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #D34A4A;
            --primary-dark: #B54A40;
            --primary-light: #D34A4A;
            --text-dark: #2D2D2D;
            --text-muted: #666666;
            --bg-light: #FDF8F7;
            --border-color: #F0E6E4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
            padding: 20px;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 4px 25px rgba(208, 98, 88, 0.12);
            border-radius: 12px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
        }

        .logo-icon svg {
            width: 100%;
            height: 100%;
        }

        .logo-text h1 {
            color: var(--text-dark);
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .logo-text p {
            color: var(--text-muted);
            font-size: 12px;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            color: var(--text-dark);
            font-size: 32px;
            font-weight: 300;
        }

        .invoice-title .invoice-number {
            color: var(--primary-color);
            font-size: 14px;
            margin-top: 5px;
            font-weight: 600;
        }

        .details-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .bill-to,
        .invoice-info {
            width: 48%;
        }

        .section-title {
            color: var(--primary-color);
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .bill-to p,
        .invoice-info p {
            color: var(--text-dark);
            font-size: 14px;
            line-height: 1.8;
        }

        .bill-to .name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-table th {
            background: var(--primary-color);
            color: #fff;
            padding: 14px 15px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .invoice-table th:first-child {
            border-radius: 8px 0 0 0;
        }

        .invoice-table th:last-child {
            border-radius: 0 8px 0 0;
            text-align: right;
        }

        .invoice-table td:last-child {
            text-align: right;
        }

        .invoice-table td {
            padding: 16px 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .invoice-table tr:hover {
            background: var(--bg-light);
        }

        .prorata-badge {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }

        .totals {
            width: 300px;
            margin-left: auto;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .totals-row.grand-total {
            border-bottom: none;
            border-top: 2px solid var(--text-dark);
            margin-top: 10px;
            padding-top: 15px;
        }

        .totals-row.grand-total .label,
        .totals-row.grand-total .value {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-paid {
            background: linear-gradient(135deg, #4caf50 0%, #43a047 100%);
            color: #fff;
        }

        .status-pending {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: #fff;
        }

        .status-late {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: #fff;
        }

        .status-refund {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            color: #fff;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
        }

        .footer p:last-child {
            color: var(--primary-color);
            font-weight: 600;
            margin-top: 5px;
        }

        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .action-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(208, 98, 88, 0.35);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #9E3F35 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(208, 98, 88, 0.45);
        }

        .action-btn.pdf-btn {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.35);
        }

        .action-btn.pdf-btn:hover {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.45);
        }

        .action-btn svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }

            .action-buttons {
                display: none;
            }
        }
    </style>
    <!-- html2pdf.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>
    <div class="action-buttons">
        <button class="action-btn pdf-btn" onclick="savePDF()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M12,19L8,15H10.5V12H13.5V15H16L12,19Z" />
            </svg>
            Save PDF
        </button>
        <button class="action-btn" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M18,3H6V7H18M19,12A1,1 0 0,1 18,11A1,1 0 0,1 19,10A1,1 0 0,1 20,11A1,1 0 0,1 19,12M16,19H8V14H16M19,8H5A3,3 0 0,0 2,11V17H6V21H18V17H22V11A3,3 0 0,0 19,8Z" />
            </svg>
            Print
        </button>
    </div>

    <div class="invoice-container">
        <div class="header">
            <div class="logo-section">
                <div class="logo-icon">
                    <!-- English Hub Castle Logo -->
                    <img src="<?= base_url('assets/images/english-hub.png') ?>" alt="English Hub Logo" width="200" height="70">
                </div>

            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <div class="invoice-number">#INV-<?= str_pad($tagihan->id_tagihan, 6, '0', STR_PAD_LEFT) ?></div>
            </div>
        </div>

        <div class="details-section">
            <div class="bill-to">
                <div class="section-title">Tagihan Kepada</div>
                <p class="name"><?= $tagihan->nama_anak ?></p>
                <p>Orang Tua: <?= $tagihan->nama_ortu ?></p>
                <p>No. HP: <?= $tagihan->no_hp ?></p>
                <p><?= $tagihan->alamat_ortu ?></p>
                <?php if ($tagihan->email) : ?>
                    <p><?= $tagihan->email ?></p>
                <?php endif; ?>
            </div>
            <div class="invoice-info">
                <div class="section-title">Info Invoice</div>
                <p><strong>Tanggal:</strong> <?= date('d F Y') ?></p>
                <p><strong>Periode:</strong> <?= date('F Y', mktime(0, 0, 0, $tagihan->bulan, 1, $tagihan->tahun)) ?></p>
                <?php if ($tagihan->metode_pembayaran) : ?>
                    <p><strong>Metode Pembayaran:</strong> <?= $tagihan->metode_pembayaran ?></p>
                <?php endif; ?>
                <p><strong>Status:</strong>
                    <?php
                    $status_class = 'status-pending';
                    if ($tagihan->status_bayar == 'Paid') $status_class = 'status-paid';
                    elseif ($tagihan->status_bayar == 'Late') $status_class = 'status-late';
                    elseif ($tagihan->status_bayar == 'Refund') $status_class = 'status-refund';
                    ?>
                    <span class="status-badge <?= $status_class ?>"><?= $tagihan->status_bayar ?></span>
                </p>
                <?php if ($tagihan->tgl_bayar && $tagihan->status_bayar == 'Paid') : ?>
                    <p><strong>Tgl Bayar:</strong> <?= date('d-m-Y', strtotime($tagihan->tgl_bayar)) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Deskripsi</th>
                    <th style="width: 20%; text-align: right;">Harga</th>
                    <th style="width: 20%; text-align: right;">Diskon</th>
                    <th style="width: 20%; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_biaya_dasar = 0;
                $total_diskon = 0;
                foreach ($items as $item) {
                    $total_biaya_dasar += $item->nilai_biaya;
                    $total_diskon += $item->nilai_diskon;
                ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text-dark);"><?= $item->tipe_biaya ?></div>
                            <div style="font-size: 12px; color: var(--text-muted);"><?= $item->keterangan ?: 'No description' ?></div>
                        </td>
                        <td style="text-align: right;">
                            Rp <?= number_format($item->nilai_biaya, 0, ',', '.') ?>
                        </td>
                        <td style="text-align: right; color: var(--primary-color);">
                            <?php if ($item->nilai_diskon > 0) : ?>
                                -Rp <?= number_format($item->nilai_diskon, 0, ',', '.') ?>
                                <div style="font-size: 10px;"><?= $item->tipe_diskon ?></div>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; font-weight: 700;">
                            Rp <?= number_format($item->subtotal, 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="totals">
            <?php if ($total_diskon > 0) : ?>
                <div class="totals-row">
                    <span class="label">Total Harga</span>
                    <span class="value">Rp <?= number_format($total_biaya_dasar, 0, ',', '.') ?></span>
                </div>
                <div class="totals-row">
                    <span class="label">Total Diskon</span>
                    <span class="value" style="color: var(--primary-color);">-Rp <?= number_format($total_diskon, 0, ',', '.') ?></span>
                </div>
            <?php endif; ?>
            <div class="totals-row grand-total">
                <span class="label">Total Tagihan</span>
                <span class="value">Rp <?= number_format($tagihan->jumlah, 0, ',', '.') ?></span>
            </div>
        </div>

        <div class="footer">
            <p>Terima kasih telah mempercayakan pendidikan putra-putri Anda kepada kami.</p>
            <p style="margin-top: 5px;"><?= isset($apk->nama_instansi) ? $apk->nama_instansi : 'English Hub' ?> - Take Your English to The Next Level</p>
        </div>
    </div>

    <script>
        function savePDF() {
            const element = document.querySelector('.invoice-container');
            const invoiceNumber = 'INV-<?= str_pad($tagihan->id_tagihan, 6, "0", STR_PAD_LEFT) ?>';
            const opt = {
                margin: 10,
                filename: invoiceNumber + '.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            // Hide buttons temporarily for clean PDF
            const buttons = document.querySelector('.action-buttons');
            buttons.style.display = 'none';

            html2pdf().set(opt).from(element).save().then(() => {
                buttons.style.display = 'flex';
            });
        }
    </script>
</body>

</html>