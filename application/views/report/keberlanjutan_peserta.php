<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .print-area,
        .print-area * {
            visibility: visible;
        }

        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>
<div class="page-inner">
    <div class="page-header">
        <h4 class="page-title">Laporan Perbandingan Keberlanjutan Peserta (Rentang Bulan)</h4>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="form-inline">
                <label class="mr-2">Dari</label>
                <select id="startBulan" class="form-control mr-2 mt-2">
                    <?php foreach (range(1, 12) as $b) : ?>
                        <option value="<?= $b ?>" <?= $b == date('n') ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $b, 10)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="startTahun" class="form-control mr-2 mt-2">
                    <?php $now = date('Y');
                    for ($i = $now - 3; $i <= $now + 1; $i++) : ?>
                        <option value="<?= $i ?>" <?= $i == $now ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>

                <label class="mr-2 mt-2">Sampai</label>
                <select id="endBulan" class="form-control mr-2 mt-2">
                    <?php foreach (range(1, 12) as $b) : ?>
                        <option value="<?= $b ?>" <?= $b == date('n') ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $b, 10)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="endTahun" class="form-control mr-2 mt-2">
                    <?php for ($i = $now - 3; $i <= $now + 1; $i++) : ?>
                        <option value="<?= $i ?>" <?= $i == $now ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>

                <button id="filterBtn" class="btn btn-primary mt-2">Tampilkan</button>
                <button class="btn btn-success mx-2 mt-2" id="printBtn"><i class="fas fa-print"></i> Cetak</button>
            </div>
        </div>
        <div class="card-body print-area">
            <div class="table-responsive">
                <table id="tablePerbandingan" class="table table-bordered">
                    <thead id="tableHeader"></thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>

        <script>
            const bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];

            function getMonthYearRange(startMonth, startYear, endMonth, endYear) {
                const result = [];
                let date = new Date(startYear, startMonth - 1);
                const endDate = new Date(endYear, endMonth - 1);

                while (date <= endDate) {
                    result.push({
                        bulan: date.getMonth() + 1,
                        tahun: date.getFullYear()
                    });
                    date.setMonth(date.getMonth() + 1);
                }
                return result;
            }

            $('#filterBtn').click(function() {
                const startBulan = parseInt($('#startBulan').val());
                const startTahun = parseInt($('#startTahun').val());
                const endBulan = parseInt($('#endBulan').val());
                const endTahun = parseInt($('#endTahun').val());

                const periode = getMonthYearRange(startBulan, startTahun, endBulan, endTahun);

                $.ajax({
                    url: '<?= site_url('Report/get_perbandingan_rentang') ?>',
                    method: 'POST',
                    data: {
                        periode: JSON.stringify(periode)
                    },
                    dataType: 'json',
                    success: function(res) {
                        let header = '<tr><th>Kelas</th>';
                        periode.forEach(p => {
                            header += `<th>${bulanIndo[p.bulan]} ${p.tahun}</th>`;
                        });
                        header += '</tr>';
                        $('#tableHeader').html(header);

                        let body = '';
                        res.forEach(row => {
                            body += `<tr><td>${row.nama_kelas}</td>`;
                            let prevTotal = null;
                            periode.forEach(p => {
                                const key = `${p.bulan}-${p.tahun}`;
                                const total = row.data[key] ?? 0;

                                let cell = `<strong>${total}</strong>`;
                                let percent = '';

                                if (prevTotal !== null) {
                                    if (prevTotal === 0) {
                                        percent = total > 0 ? `${(total * 100)}%` : '0%';
                                    } else {
                                        percent = (((total - prevTotal) / prevTotal) * 100).toFixed(0) + '%';
                                    }

                                    let color = '';
                                    const percentValue = parseFloat(percent);
                                    if (percentValue > 0) color = 'green';
                                    else if (percentValue < 0) color = 'red';
                                    else color = 'blue';

                                    if (!(prevTotal === 0 && total === 0)) {
                                        cell += `<br><span style="color:${color}; font-size: 12px;">${percent}</span>`;
                                    }
                                }

                                prevTotal = total;
                                body += `<td>${cell}</td>`;
                            });
                            body += '</tr>';
                        });
                        $('#tableBody').html(body);
                    }

                });
            });

            $('#printBtn').click(function() {
                var table = document.getElementById("tablePerbandingan");
                var header = document.getElementById("tableHeader").outerHTML;
                var body = document.getElementById("tableBody").outerHTML;

                var newWin = window.open("");
                newWin.document.write('<html><head><title>Laporan Keberlanjutan Peserta</title>');
                newWin.document.write('<style>');
                newWin.document.write('body { font-family: Arial, sans-serif; }');
                newWin.document.write('table { border-collapse: collapse; width: 100%; margin-top: 20px; }');
                newWin.document.write('th, td { border: 1px solid #000; padding: 6px; text-align: center; }');
                newWin.document.write('</style>');
                newWin.document.write('</head><body>');
                newWin.document.write('<h3 style="text-align:center;">Laporan Perbandingan Keberlanjutan Peserta</h3>');
                newWin.document.write('<table>' + header + body + '</table>');
                newWin.document.write('</body></html>');
                newWin.print();
                newWin.close();
            });
            $('#filterBtn').click(); // Load default
        </script>