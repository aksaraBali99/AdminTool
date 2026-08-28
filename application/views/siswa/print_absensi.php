<!DOCTYPE html>
<html>
<head>
    <title>Rekap Absensi - <?= $siswa->nama_anak ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            padding: 20px;
        }
        .container {
            max-width: 210mm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            font-size: 14px;
            color: #34495e;
            font-weight: normal;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .info-left, .info-right {
            width: 48%;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: 600;
            width: 120px;
            color: #555;
        }
        .info-value {
            flex: 1;
        }
        .rekap-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }
        .rekap-box {
            flex: 1;
            text-align: center;
            padding: 15px 10px;
            border-radius: 8px;
            color: #fff;
        }
        .rekap-box.hadir { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .rekap-box.izin { background: linear-gradient(135deg, #f39c12, #f1c40f); }
        .rekap-box.alpha { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .rekap-box.total { background: linear-gradient(135deg, #3498db, #2980b9); }
        .rekap-box .number {
            font-size: 28px;
            font-weight: bold;
            display: block;
        }
        .rekap-box .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th {
            background: #2c3e50;
            color: #fff;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        table tr:hover {
            background: #f0f0f0;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-hadir { background: #d4edda; color: #155724; }
        .status-izin { background: #fff3cd; color: #856404; }
        .status-alpha { background: #f8d7da; color: #721c24; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #777;
        }
        .signature-area {
            text-align: center;
            margin-top: 40px;
        }
        .signature-box {
            display: inline-block;
            width: 200px;
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 50px;
            margin-bottom: 5px;
        }
        @media print {
            body { padding: 0; }
            .container { max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= $apk[0]->nama_apk ?></h1>
            <h2>Rekap Kehadiran Siswa</h2>
        </div>
        
        <div class="info-section">
            <div class="info-left">
                <div class="info-row">
                    <span class="info-label">Nama Siswa</span>
                    <span class="info-value">: <strong><?= $siswa->nama_anak ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nama Orang Tua</span>
                    <span class="info-value">: <?= $siswa->nama_ortu ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jenis Kelas</span>
                    <span class="info-value">: <?= $siswa->nama_kelas ?></span>
                </div>
            </div>
            <div class="info-right">
                <div class="info-row">
                    <span class="info-label">Periode</span>
                    <span class="info-value">: <?= date('d/m/Y', strtotime($date_from)) ?> - <?= date('d/m/Y', strtotime($date_to)) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Cetak</span>
                    <span class="info-value">: <?= date('d/m/Y H:i') ?></span>
                </div>
            </div>
        </div>

        <div class="rekap-section">
            <div class="rekap-box hadir">
                <span class="number"><?= $rekap->total_hadir ?: 0 ?></span>
                <span class="label">Hadir</span>
            </div>
            <div class="rekap-box izin">
                <span class="number"><?= $rekap->total_izin ?: 0 ?></span>
                <span class="label">Izin</span>
            </div>
            <div class="rekap-box alpha">
                <span class="number"><?= $rekap->total_alpha ?: 0 ?></span>
                <span class="label">Alpha</span>
            </div>
            <div class="rekap-box total">
                <span class="number"><?= $rekap->total_hari ?: 0 ?></span>
                <span class="label">Total Hari</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="8%">No</th>
                    <th width="20%">Tanggal</th>
                    <th width="15%">Hari</th>
                    <th width="17%">Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($absensi)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #999;">
                            Tidak ada data absensi untuk periode ini
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    foreach($absensi as $a): 
                        $day_num = date('w', strtotime($a->tanggal));
                        $status_class = strtolower($a->status_hadir);
                    ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($a->tanggal)) ?></td>
                        <td><?= $hari[$day_num] ?></td>
                        <td>
                            <span class="status-badge status-<?= $status_class ?>">
                                <?= $a->status_hadir ?>
                            </span>
                        </td>
                        <td><?= $a->keterangan ?: '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signature-area">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Kepala Program</div>
            </div>
        </div>

        <div class="footer">
            <div>Dokumen ini dicetak secara otomatis oleh sistem <?= $apk[0]->nama_apk ?></div>
            <div>Halaman 1 dari 1</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
