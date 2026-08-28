<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Payroll Staff</h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-add">
                        <i class="fa fa-plus mr-2"></i> Tambah Payroll
                    </button>
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
                    <div class="col-md-2">
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
                                <th>Nama</th>
                                <th>Posisi</th>
                                <th>Gaji Pokok</th>
                                <th>Tunjangan</th>
                                <th>Potongan</th>
                                <th>Pot. Tambahan</th>
                                <th>PPh21</th>
                                <th>Gaji Bersih</th>
                                <th>Status</th>
                                <th width="180"><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div class="modal fade" id="modal-payroll" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-payroll">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Payroll Staff</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id">
                            <input type="hidden" name="tipe_form" value="add">
                            
                            <div class="form-group">
                                <label>Karyawan <span class="text-danger">*</span></label>
                                <select name="id_karyawan" class="form-control" required>
                                    <option value="">-- Pilih Karyawan --</option>
                                    <?php foreach ($karyawan as $k): ?>
                                    <option value="<?= $k->id_karyawan ?>" data-gaji="<?= $k->gaji_pokok ?>" data-tunjangan="<?= $k->tunjangan ?>"><?= $k->nama ?> - <?= $k->posisi ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Bulan <span class="text-danger">*</span></label>
                                        <select name="bulan" class="form-control" required>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?= $i ?>" <?= date('n') == $i ? 'selected' : '' ?>><?= $bulan_nama[$i] ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tahun <span class="text-danger">*</span></label>
                                        <select name="tahun" class="form-control" required>
                                            <?php for ($y = date('Y'); $y >= date('Y') - 1; $y--): ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Gaji Pokok <span class="text-danger">*</span></label>
                                        <input type="number" name="gaji_pokok" class="form-control" required value="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tunjangan</label>
                                        <input type="number" name="tunjangan" class="form-control" value="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Potongan</label>
                                        <input type="number" name="potongan" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
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
                        <h5 class="modal-title">Kelola Potongan Tambahan</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="potongan_id_payroll">
                        
                        <form id="form-potongan" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nominal <span class="text-danger">*</span></label>
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
                                        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
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
                                    <th colspan="2">Total</th>
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
            url: '<?= site_url("Hr/payroll_staff_page") ?>',
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
                var status = row[10]; // Updated index
                var btns = '';
                
                if (status == 'draft') {
                    btns += `<button class='btn btn-sm btn-primary btn-edit' data-id="${row[0]}" title="Edit"><i class='fa fa-edit'></i></button> `;
                    btns += `<button class='btn btn-sm btn-warning btn-potongan' data-id="${row[0]}" title="Potongan"><i class='fa fa-minus-circle'></i></button> `;
                    btns += `<button class='btn btn-sm btn-success btn-approve' data-id="${row[0]}" title="Approve"><i class='fa fa-check'></i></button> `;
                    btns += `<button class='btn btn-sm btn-danger btn-hapus' data-id="${row[0]}" title="Hapus"><i class='fa fa-trash'></i></button> `;
                } else if (status == 'approved') {
                    btns += `<button class='btn btn-sm btn-primary btn-pay' data-id="${row[0]}" title="Bayar"><i class='fa fa-money-bill'></i></button> `;
                }
                
                btns += `<a href='<?= site_url("Hr/payslip_staff") ?>/${row[0]}' class='btn btn-sm btn-info' title="Payslip"><i class='fa fa-file-pdf'></i></a>`;
                
                return `<center>${btns}</center>`;
            }
        }],
        "order": [[1, 'asc']]
    });

    $('#btn-filter').click(function() { table.ajax.reload(); });

    $("select[name='id_karyawan']").change(function() {
        var gaji = $(this).find(':selected').data('gaji') || 0;
        var tunjangan = $(this).find(':selected').data('tunjangan') || 0;
        $("input[name='gaji_pokok']").val(gaji);
        $("input[name='tunjangan']").val(tunjangan);
    });

    $('#btn-add').click(function() {
        $('#label_tipe').text('Tambah');
        $('#form-payroll')[0].reset();
        $("input[name='tipe_form']").val('add');
        $('#modal-payroll').modal('show');
    });

    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.post('<?= site_url("Hr/get_payroll_staff") ?>', {id: id}, function(res) {
            var data = JSON.parse(res);
            $('#label_tipe').text('Edit');
            $("input[name='tipe_form']").val('edit');
            $("input[name='id']").val(data.id);
            $("select[name='id_karyawan']").val(data.id_karyawan);
            $("select[name='bulan']").val(data.bulan);
            $("select[name='tahun']").val(data.tahun);
            $("input[name='gaji_pokok']").val(data.gaji_pokok);
            $("input[name='tunjangan']").val(data.tunjangan);
            $("input[name='potongan']").val(data.potongan);
            $("textarea[name='keterangan']").val(data.keterangan);
            $('#modal-payroll').modal('show');
        });
    });

    $(document).on('click', '.btn-approve', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Approve Payroll?',
            icon: 'question',
            showCancelButton: true
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Hr/approve_payroll_staff") ?>', {id: id}, function(res) {
                    var data = JSON.parse(res);
                    if (data.status == 'success') {
                        Swal.fire('Berhasil', 'Payroll di-approve', 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-pay', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Bayar Payroll?',
            text: 'Akan ditambahkan ke pengeluaran',
            icon: 'question',
            showCancelButton: true
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Hr/pay_payroll_staff") ?>', {id: id}, function(res) {
                    var data = JSON.parse(res);
                    if (data.status == 'success') {
                        Swal.fire('Berhasil', 'Payroll dibayar', 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    });

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
                $.post('<?= site_url("Hr/hapus_payroll_staff") ?>', {id: id}, function(res) {
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
        $.post('<?= site_url("Hr/get_potongan") ?>', {payroll_type: 'staff', id_payroll: id_payroll}, function(res) {
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
            payroll_type: 'staff',
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
        $.post('<?= site_url("Hr/hapus_potongan") ?>', {id: id, payroll_type: 'staff', id_payroll: id_payroll}, function(res) {
            var data = JSON.parse(res);
            if (data.status == 'success') {
                loadPotongan(id_payroll);
                table.ajax.reload();
            }
        });
    });

    $('#form-payroll').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Hr/add_payroll_staff") ?>', $(this).serialize(), function(res) {
            var data = JSON.parse(res);
            if (data.status == 'success') {
                Swal.fire('Sukses', 'Payroll berhasil disimpan', 'success');
                $('#modal-payroll').modal('hide');
                table.ajax.reload();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
});
</script>
