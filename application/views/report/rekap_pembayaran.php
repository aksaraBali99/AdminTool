<style>
    @media print {

        /* Sembunyikan kolom terakhir (Action) di thead, tbody, dan tfoot */
        table th:last-child,
        table td:last-child,
        table tfoot th:last-child {
            display: none;
        }

        /* Opsional: rapikan border */
        table {
            border-collapse: collapse;
            width: 100%;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 6px;
        }
    }
</style>
<div class="page-inner">
    <div class="page-header">
        <h4 class="page-title">Laporan Rekap Pembayaran Peserta</h4>
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

                <button class="btn btn-primary mr-1 mt-2" id="filterBtn">Tampilkan</button>
                <button class="btn btn-success mx-1 mt-2" id="printBtn">Print</button>
            </div>
        </div>
        <div class="card-body">
            <table id="rekapTable" class="table table-bordered table-striped table-responsive" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 20%;">Jenis Kelas</th>
                        <th style="width: 10%;">Jumlah Peserta</th>
                        <th style="width: 15%;">Total Tagihan</th>
                        <th style="width: 15%;">Total Sudah Bayar</th>
                        <th style="width: 15%;">Total Belum Bayar</th>
                        <th style="width: 10%;" class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada Data</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total</th>
                        <th id="totalTagihan"></th>
                        <th id="totalSudahBayar"></th>
                        <th id="totalBelumBayar"></th>
                        <th class="no-print"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Modal Detail Peserta -->
        <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Detail Peserta</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table id="pesertaDetailTable" class="table table-bordered table-responsive" style="width: 100%;">
                            <thead>
                                <tr>

                                    <th style="width: 5%;">No</th>
                                    <th style="width: 30%;">Nama</th>
                                    <th style="width: 20%;">No. HP</th>
                                    <th style="width: 20%;">Tgl Bayar</th>
                                    <th style="width: 25%;">Jumlah Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">Total</th>
                                    <th id="subtotalBayar"></th>
                                </tr>
                            </tfoot>
                        </table>
                        <button class="btn btn-success" id="printDetailBtn">Print Detail</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $('#filterBtn').click(function() {
                var bulan = $('#filterBulan').val();
                var tahun = $('#filterTahun').val();

                $.ajax({
                    url: '<?= site_url('Report/get_rekap_pembayaran') ?>',
                    method: 'POST',
                    data: {
                        bulan: bulan,
                        tahun: tahun
                    },
                    dataType: 'json',
                    success: function(res) {
                        var html = '';
                        var no = 1;
                        var totalTagihan = 0;
                        var totalSudahBayar = 0;
                        var totalBelumBayar = 0;

                        res.forEach(function(r) {

                            totalTagihan += parseInt(r.total_tagihan);
                            totalSudahBayar += parseInt(r.total_sudah_bayar);
                            totalBelumBayar += parseInt(r.total_belum_bayar);

                            html += '<tr>';
                            html += '<td>' + no++ + '</td>';
                            html += '<td>' + r.nama_kelas + '</td>';
                            html += '<td>' + r.jumlah_peserta + '</td>';
                            html += '<td>' + formatCurrency(r.total_tagihan) + '</td>';
                            html += '<td>' + formatCurrency(r.total_sudah_bayar) + '</td>';
                            html += '<td>' + formatCurrency(r.total_belum_bayar) + '</td>';
                            html += '<td class="no-print"><button class="btn btn-info btn-sm" onclick="lihatDetail(\'' + r.id_jenis_kelas + '\')">Lihat Detail</button></td>';
                            html += '</tr>';
                        });

                        $('#rekapTable tbody').html(html);

                        // Update subtotal di tfoot
                        var totalUangAll = totalSudahBayar + totalBelumBayar;
                        $('#totalTagihan').text(formatCurrency(totalTagihan));
                        $('#totalSudahBayar').text(formatCurrency(totalSudahBayar));
                        $('#totalBelumBayar').text(formatCurrency(totalBelumBayar));
                    }
                });
            });

            function formatCurrency(num) {
                if (!num || isNaN(num)) return 'Rp 0';
                return 'Rp ' + Number(num).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function lihatDetail(idKelas) {
                var bulan = $('#filterBulan').val();
                var tahun = $('#filterTahun').val();
                $.ajax({
                    url: '<?= site_url('Report/get_peserta_by_kelas') ?>',
                    method: 'POST',
                    data: {
                        id_kelas: idKelas,
                        bulan: bulan,
                        tahun: tahun
                    },
                    dataType: 'json',
                    success: function(res) {
                        var html = '';
                        var no = 1;
                        var subtotal = 0;

                        res.forEach(function(r) {
                            var jumlahBayar = parseInt(r.jumlah_bayar) || 0;
                            subtotal += jumlahBayar;

                            html += '<tr>';
                            html += '<td>' + no++ + '</td>';
                            html += '<td>' + r.nama + '</td>';
                            html += '<td>' + r.no_wa + '</td>';
                            html += '<td>' + formatDate(r.tgl_bayar) + '</td>';
                            html += '<td>' + formatCurrency(jumlahBayar) + '</td>';
                            html += '</tr>';
                        });

                        $('#pesertaDetailTable tbody').html(html);
                        $('#subtotalBayar').text(formatCurrency(subtotal)); // tampilkan subtotal di <tfoot>
                        $('#detailModal').modal('show');
                    }
                });
            }

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

            // Tombol Print Rekap
            $('#printBtn').click(function() {
                var content = document.getElementById('rekapTable').outerHTML;
                var printWindow = window.open('', '', 'height=600,width=800');
                printWindow.document.write(`
        <html>
            <head>
                <title>Laporan Rekap Pembayaran</title>
                <style>
                    @media print {
                        .no-print { display: none !important; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
                        th { background: #f2f2f2; }
                    }
                </style>
            </head>
            <body>
                ${content}
            </body>
        </html>
    `);
                printWindow.document.close();
                printWindow.print();
            });

            // Tombol Print Detail
            $('#printDetailBtn').click(function() {
                var content = document.getElementById('pesertaDetailTable').outerHTML;
                var printWindow = window.open('', '', 'height=600,width=800');
                printWindow.document.write(`
        <html>
            <head>
                <title>Detail Peserta</title>
                <style>
                    @media print {
                        .no-print { display: none !important; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
                        th { background: #f2f2f2; }
                    }
                </style>
            </head>
            <body>
                ${content}
            </body>
        </html>
    `);
                printWindow.document.close();
                printWindow.print();
            });
        </script>
    </div>