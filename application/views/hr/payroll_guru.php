<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Payroll Guru</h4>
                    <div class="ml-auto">
                        <button class="btn btn-success" id="btn-generate-all">
                            <i class="fa fa-cog mr-1"></i> Generate Semua
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label>Bulan</label>
                        <select id="filter_bulan" class="form-control">
                            <?php 
                            $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= date('n') == $i ? 'selected' : '' ?>><?= $bulan_nama[$i] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Tahun</label>
                        <select id="filter_tahun" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Status</label>
                        <select id="filter_status" class="form-control">
                            <option value="">-- Semua --</option>
                            <option value="draft">Draft</option>
                            <option value="approved">Approved</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-info btn-block" id="btn-filter">
                            <i class="fa fa-search"></i> Filter
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="payroll-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Guru</th>
                                <th>Jam Ajar</th>
                                <th>Honor</th>
                                <th>Hari Hadir</th>
                                <th>Kedatangan</th>
                                <th>Transport</th>
                                <th>PPh21</th>
                                <th>Potongan</th>
                                <th>Gaji Bersih</th>
                                <th>Status</th>
                                <th width="150"><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Generate -->
        <div class="modal fade" id="modal-generate" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-generate">
                        <div class="modal-header">
                            <h5 class="modal-title">Generate Payroll Guru</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Payroll akan dihitung otomatis dari data absensi guru
                            </div>
                            
                            <div class="form-group">
                                <label>Pilih Guru</label>
                                <select name="id_guru" class="form-control" required>
                                    <option value="">-- Pilih Guru --</option>
                                    <?php foreach ($pengajar as $p): ?>
                                    <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Bulan</label>
                                        <select name="bulan" class="form-control">
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?= $i ?>" <?= date('n') == $i ? 'selected' : '' ?>><?= $bulan_nama[$i] ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tahun</label>
                                        <select name="tahun" class="form-control">
                                            <?php for ($y = date('Y'); $y >= date('Y') - 1; $y--): ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Generate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modal Potongan -->
        <div class="modal fade" id="modal-potongan" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Kelola Potongan Payroll</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="potongan_id_payroll">
                        
                        <!-- Form Input Potongan -->
                        <form id="form-potongan" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nominal Potongan <span class="text-danger">*</span></label>
                                        <input type="number" name="nominal" class="form-control" required min="0" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Keterangan <span class="text-danger">*</span></label>
                                        <input type="text" name="keterangan" class="form-control" required placeholder="Pinjaman, Denda, dll...">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> Tambah</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <!-- List Potongan -->
                        <table class="table table-bordered" id="table-potongan">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Keterangan</th>
                                    <th>Nominal</th>
                                    <th width="80">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr class="table-warning">
                                    <th colspan="2">Total Potongan</th>
                                    <th id="total-potongan">Rp 0</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#payroll-table').DataTable({
        "ajax": {
            url: '<?= site_url("Hr/payroll_guru_page") ?>',
            type: 'POST',
            data: function(d) {
                d.bulan = $('#filter_bulan').val();
                d.tahun = $('#filter_tahun').val();
                d.status = $('#filter_status').val();
            }
        },
        "columnDefs": [{
            "targets": -1,
            "render": function(data, type, row) {
                var status = row[11]; // Status column index updated
                var btns = '';
                
                if (status == 'draft') {
                    btns += `<button class='btn btn-sm btn-warning my-1 btn-potongan' data-id="${row[0]}" title="Potongan"><i class='fa fa-minus-circle'></i></button> `;
                    btns += `<button class='btn btn-sm btn-success my-1 btn-approve' data-id="${row[0]}" title="Approve"><i class='fa fa-check'></i></button> `;
                    btns += `<button class='btn btn-sm btn-danger my-1 btn-hapus' data-id="${row[0]}" title="Hapus"><i class='fa fa-trash'></i></button> `;
                } else if (status == 'approved') {
                    btns += `<button class='btn btn-sm btn-primary my-1 btn-pay' data-id="${row[0]}" title="Bayar"><i class='fa fa-money-bill'></i></button> `;
                }
                
                btns += `<a href='<?= site_url("Hr/payslip_guru") ?>/${row[0]}' class='btn btn-sm btn-info' title="Payslip"><i class='fa fa-file-pdf'></i></a>`;
                
                return `<center>${btns}</center>`;
            }
        }],
        "order": [[1, 'asc']]
    });

    $('#btn-filter').click(function() { table.ajax.reload(); });

    $('#btn-generate-all').click(function() {
        Swal.fire({
            title: 'Generate Payroll Semua Guru?',
            text: 'Ini akan menghitung payroll untuk semua guru berdasarkan absensi',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Generate'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Hr/generate_all_payroll_guru") ?>', {
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val()
                }, function(res) {
                    var data = JSON.parse(res);
                    Swal.fire('Selesai', data.message, 'success');
                    table.ajax.reload();
                });
            }
        });
    });

    $(document).on('click', '.btn-approve', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Approve Payroll?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Approve'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Hr/approve_payroll_guru") ?>', {id: id}, function(res) {
                    var data = JSON.parse(res);
                    if (data.status == 'success') {
                        Swal.fire('Berhasil', 'Payroll di-approve', 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-pay', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Bayar Payroll?',
            text: 'Ini akan menandai payroll sebagai dibayar dan menambah ke pengeluaran',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Bayar'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Hr/pay_payroll_guru") ?>', {id: id}, function(res) {
                    var data = JSON.parse(res);
                    if (data.status == 'success') {
                        Swal.fire('Berhasil', data.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    });

    // Hapus Payroll
    $(document).on('click', '.btn-hapus', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Payroll?',
            text: 'Data payroll akan dihapus permanen',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Hr/hapus_payroll_guru") ?>', {id: id}, function(res) {
                    var data = JSON.parse(res);
                    if (data.status == 'success') {
                        Swal.fire('Berhasil', 'Payroll dihapus', 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    });

    // Potongan Modal
    $(document).on('click', '.btn-potongan', function() {
        var id = $(this).data('id');
        $('#potongan_id_payroll').val(id);
        $('#form-potongan')[0].reset();
        loadPotongan(id);
        $('#modal-potongan').modal('show');
    });

    function loadPotongan(id_payroll) {
        $.post('<?= site_url("Hr/get_potongan") ?>', {payroll_type: 'guru', id_payroll: id_payroll}, function(res) {
            var data = JSON.parse(res);
            var html = '';
            var total = 0;
            
            $.each(data, function(i, item) {
                total += parseFloat(item.nominal);
                html += `<tr>
                    <td>${i+1}</td>
                    <td>${item.keterangan}</td>
                    <td>Rp ${numberFormat(item.nominal)}</td>
                    <td><button class='btn btn-sm btn-danger btn-hapus-potongan' data-id="${item.id}"><i class='fa fa-trash'></i></button></td>
                </tr>`;
            });
            
            if (data.length == 0) {
                html = '<tr><td colspan="4" class="text-center text-muted">Belum ada potongan</td></tr>';
            }
            
            $('#table-potongan tbody').html(html);
            $('#total-potongan').text('Rp ' + numberFormat(total));
        });
    }

    function numberFormat(num) {
        return parseFloat(num).toLocaleString('id-ID');
    }

    $('#form-potongan').submit(function(e) {
        e.preventDefault();
        var id_payroll = $('#potongan_id_payroll').val();
        $.post('<?= site_url("Hr/add_potongan") ?>', {
            payroll_type: 'guru',
            id_payroll: id_payroll,
            nominal: $("input[name='nominal']").val(),
            keterangan: $("input[name='keterangan']").val()
        }, function(res) {
            var data = JSON.parse(res);
            if (data.status == 'success') {
                $('#form-potongan')[0].reset();
                loadPotongan(id_payroll);
                table.ajax.reload();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });

    $(document).on('click', '.btn-hapus-potongan', function() {
        var id = $(this).data('id');
        var id_payroll = $('#potongan_id_payroll').val();
        $.post('<?= site_url("Hr/hapus_potongan") ?>', {id: id, payroll_type: 'guru', id_payroll: id_payroll}, function(res) {
            var data = JSON.parse(res);
            if (data.status == 'success') {
                loadPotongan(id_payroll);
                table.ajax.reload();
            }
        });
    });
});
</script>
