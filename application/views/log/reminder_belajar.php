<div class="page-inner">
    <div class="page-header">
        <h4 class="page-title">Reminder Belajar WhatsApp</h4>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <form id="filterForm" class="form-inline">
                        <select id="filterBulan" class="form-control mr-2">
                            <?php for ($b = 1; $b <= 12; $b++) : ?>
                                <option value="<?= $b ?>" <?= $b == date('n') ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $b, 10)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                        <select id="filterTahun" class="form-control mr-2 mt-2">
                            <?php $thn = date('Y'); ?>
                            <?php for ($i = $thn - 3; $i <= $thn + 1; $i++) : ?>
                                <option value="<?= $i ?>" <?= $i == $thn ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>

                        <button type="button" class="btn btn-primary mt-2" id="filterBtn">Filter</button>
                    </form>
                </div>
                <div class="card-body">
                    <table id="logWaTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>No. HP</th>
                                <th>Bulan</th>
                                <th>Tahun</th>
                                <th>Pesan</th>
                                <th>Response</th>
                                <th>Keterangan</th>
                                <th>Waktu Kirim</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <script>
                var table = $('#logWaTable').DataTable({
                    scrollX: true,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '<?= site_url('Trans/reminder_belajar_page') ?>',
                        type: 'POST',
                        data: function(d) {
                            d.bulan = $('#filterBulan').val();
                            d.tahun = $('#filterTahun').val();
                        }
                    },
                    columnDefs: [{
                        targets: [0], // Target pertama
                        orderable: false // Menonaktifkan pengurutan pada kolom pertama
                    }],
                    columns: [{
                            data: 0,
                            title: 'No'
                        }, // First column
                        {
                            data: 1,
                            title: 'Nama'
                        }, // Second column
                        {
                            data: 2,
                            title: 'No HP'
                        }, // Third column
                        {
                            data: 3,
                            title: 'Bulan'
                        }, // Fourth column
                        {
                            data: 4,
                            title: 'Tahun'
                        }, // Fifth column
                        {
                            data: 5,
                            title: 'Pesan'
                        }, // Sixth column
                        {
                            data: 6,
                            title: 'Response'
                        }, // Seventh column
                        {
                            data: 7,
                            title: 'Waktu Kirim'
                        }, // Eighth column
                        {
                            data: 8,
                            title: 'Keterangan'
                        } // Ninth column
                    ]
                });
                $('#filterBulan, #filterTahun').on('change', function() {
                    table.draw();
                });
            </script>
        </div>