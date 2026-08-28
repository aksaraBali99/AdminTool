<!-- View CodeIgniter -->
<div class="page-inner">
    <div class="page-header">
        <h4 class="page-title">Laporan Peserta Aktif</h4>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="form-inline">
                <select id="filterBulan" class="form-control mr-2 mt-2">
                    <?php
                    $bulan_arr = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    foreach ($bulan_arr as $i => $b) {
                        $selected = $i == date('n') ? 'selected' : '';
                        echo "<option value='$i' $selected>$b</option>";
                    }
                    ?>
                </select>

                <select id="filterTahun" class="form-control mr-2 mt-2">
                    <?php $thn = date('Y');
                    for ($i = $thn - 3; $i <= $thn + 1; $i++) : ?>
                        <option value="<?= $i ?>" <?= $i == $thn ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>

                <select id="filterKelas" class="form-control mr-2 mt-2">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelas as $k) : ?>
                        <option value="<?= $k->id_jenis_kelas ?>"><?= $k->nama_kelas ?></option>
                    <?php endforeach; ?>
                </select>

                <button class="btn btn-primary mr-2 mt-2" id="filterBtn">Tampilkan</button>
                <button class="btn btn-success mt-2" id="printBtn"><i class="fas fa-print"></i> Cetak</button>
            </div>
        </div>
        <div class="card-body">
            <table id="laporanTable" class="table table-bordered table-striped " style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 30%;">Nama</th>
                        <th style="width: 15%;">Kelas</th>
                        <th style="width: 15%;">No. HP</th>
                        <th style="width: 10%;">Bulan</th>
                        <th style="width: 15%;">Tahun</th>
                        <th style="width: 15%;">Tgl Bayar</th>

                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <script>
            var bulanIndo = [
                '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];

            var laporanTable = $('#laporanTable').DataTable({
                order: [
                    [1, 'asc']
                ], // Urut berdasarkan nama
                paging: true,
                searching: true,
                scrollX: true,
                destroy: true,
            });

            $('#filterBtn').click(function() {
                var bulan = $('#filterBulan').val();
                var tahun = $('#filterTahun').val();
                var kelas = $('#filterKelas').val();

                $.ajax({
                    url: '<?= site_url('Report/get_laporan_peserta') ?>',
                    method: 'POST',
                    data: {
                        bulan: bulan,
                        tahun: tahun,
                        id_kelas: kelas
                    },
                    dataType: 'json',
                    success: function(res) {
                        laporanTable.clear().draw();
                        var no = 1;
                        res.forEach(function(r) {
                            laporanTable.row.add([
                                no++,
                                r.nama,
                                r.nama_kelas,
                                r.no_wa,
                                bulanIndo[parseInt(r.bulan)],
                                r.tahun,
                                formatDate(r.tgl_bayar)
                            ]).draw(false);
                        });
                    }
                });
            });

            function formatDate(datetime) {
                if (!datetime) return '-';
                let d = new Date(datetime);
                let tgl = d.getDate().toString().padStart(2, '0');
                let bln = (d.getMonth() + 1).toString().padStart(2, '0');
                let thn = d.getFullYear();
                let jam = d.getHours().toString().padStart(2, '0');
                let menit = d.getMinutes().toString().padStart(2, '0');
                return `${tgl}-${bln}-${thn} ${jam}:${menit}`;
            }

            // Tombol Cetak
            $('#printBtn').click(function() {
                var divToPrint = document.getElementById("laporanTable");
                var newWin = window.open("");
                newWin.document.write('<html><head><title>Laporan Peserta Aktif</title>');
                newWin.document.write('<style>table {border-collapse: collapse; width: 100%;} th, td {border: 1px solid #000; padding: 6px;}</style>');
                newWin.document.write('</head><body>');
                newWin.document.write(divToPrint.outerHTML);
                newWin.document.write('</body></html>');
                newWin.print();
                newWin.close();
            });

            // Load default
            $('#filterBtn').click();
        </script>