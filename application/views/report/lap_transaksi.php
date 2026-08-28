<div class="card">
    <div class="card-header">
        <h3 class="card-title">Laporan Pemasukan & Pengeluaran</h3>
    </div>
    <div class="card-body">
        <form id="filter-form" class="form-inline mb-3">
            <div class="form-group mr-2">
                <label for="bulan" class="mr-2">Bulan</label>
                <select name="bulan" id="bulan" class="form-control">
                    <?php
                    $current_bulan = date('n'); // Ambil bulan sekarang
                    for ($i = 1; $i <= 12; $i++) : ?>
                        <option value="<?= $i ?>" <?= ($i == $current_bulan) ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 10)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="tahun" class="mr-2">Tahun</label>
                <input type="number" name="tahun" id="tahun" value="<?= date('Y') ?>" class="form-control" min="2000" max="2100">
            </div>
            <div class="form-group mr-2">
                <label for="tipe" class="mr-2">Tipe</label>
                <select name="tipe" id="tipe" class="form-control">
                    <option value="semua">Semua</option>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <button type="button" id="btn-filter" class="btn btn-primary ">Tampilkan</button>
            <button type="button" id="btn-cetak" class="btn btn-success ml-2 ">Cetak</button>
        </form>

        <div class="table-responsive">
            <table id="laporan-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Kategori/Deskripsi</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total</th>
                        <th id="total-nominal" colspan="2">Rp 0</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <script>
            $(document).ready(function() {
                var table = $('#laporan-table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ordering: false,
                    ajax: {
                        url: "<?= site_url('Report/lap_transaksi_page') ?>",
                        type: "POST",
                        data: function(d) {
                            d.bulan = $('#bulan').val();
                            d.tahun = $('#tahun').val();
                            d.tipe = $('#tipe').val();
                        },
                        dataSrc: function(json) {
                            $('#total-nominal').text(json.total_formatted);
                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'no'
                        },
                        {
                            data: 'tanggal'
                        },
                        {
                            data: 'tipe'
                        },
                        {
                            data: 'deskripsi'
                        },
                        {
                            data: 'jumlah'
                        },
                        {
                            data: 'keterangan'
                        }
                    ]
                });

                $('#btn-filter').click(function() {
                    table.ajax.reload();
                });


                $('#btn-cetak').click(function() {
                    var divToPrint = document.getElementById("laporan-table");
                    var newWin = window.open("");
                    newWin.document.write('<html><head><title>Laporan Transaksi</title>');
                    newWin.document.write('<style>table {border-collapse: collapse; width: 100%;} th, td {border: 1px solid #000; padding: 6px;}</style>');
                    newWin.document.write('</head><body>');
                    newWin.document.write(divToPrint.outerHTML);
                    newWin.document.write('</body></html>');
                    newWin.print();
                    newWin.close();
                });
            });
        </script>