<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Jadwal Libur Nasional</h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-add">
                        <i class="fa fa-plus mr-2"></i> Tambah Libur
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <select id="filter_tahun" class="form-control">
                            <?php for($y = date('Y'); $y <= date('Y')+1; $y++): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-info" id="btn-filter">
                            <i class="fa fa-search"></i> Filter
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="libur-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Jenis</th>
                                <th><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="modal-libur" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-libur">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Libur</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id">
                            <input type="hidden" name="tipe_form" value="add">
                            
                            <div class="form-group">
                                <label>Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Keterangan <span class="text-danger">*</span></label>
                                <input type="text" name="keterangan" class="form-control" required placeholder="Nama hari libur...">
                            </div>
                            
                            <div class="form-group">
                                <label>Jenis Libur <span class="text-danger">*</span></label>
                                <select name="jenis_libur" class="form-control" required>
                                    <option value="Nasional">Libur Nasional</option>
                                    <option value="Cuti Bersama">Cuti Bersama</option>
                                    <option value="Libur Khusus">Libur Khusus</option>
                                </select>
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
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#libur-table').DataTable({
        "ajax": {
            url: '<?= site_url("Penjadwalan/libur_nasional_page") ?>',
            type: 'POST',
            data: function(d) {
                d.tahun = $('#filter_tahun').val();
            }
        },
        "columnDefs": [{
            "targets": -1,
            "render": function(data, type, row) {
                return `<center>
                    <button class='btn btn-link btn-primary btn-edit' data-id="${row[0]}" title="Edit">
                        <i class='fa fa-edit'></i>
                    </button>
                    <button class='btn btn-link btn-danger btn-hapus' data-id="${row[0]}" title="Hapus">
                        <i class='fa fa-times'></i>
                    </button>
                </center>`;
            }
        }],
        "order": [[1, 'asc']]
    });

    $('#btn-filter').click(function() {
        table.ajax.reload();
    });

    $('#btn-add').click(function() {
        $('#label_tipe').text('Tambah');
        $('#form-libur')[0].reset();
        $("input[name='tipe_form']").val('add');
        $('#modal-libur').modal('show');
    });

    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.post('<?= site_url("Penjadwalan/get_libur_by_id") ?>', {id: id}, function(res) {
            var data = JSON.parse(res);
            $('#label_tipe').text('Edit');
            $("input[name='tipe_form']").val('edit');
            $("input[name='id']").val(data.id);
            $("input[name='tanggal']").val(data.tanggal);
            $("input[name='keterangan']").val(data.keterangan);
            $("select[name='jenis_libur']").val(data.jenis_libur);
            $('#modal-libur').modal('show');
        });
    });

    $(document).on('click', '.btn-hapus', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus libur ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Penjadwalan/hapus_libur") ?>', {id: id}, function(res) {
                    if (res.includes('sukses')) {
                        Swal.fire('Berhasil', 'Libur dihapus', 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    });

    $('#form-libur').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Penjadwalan/add_libur") ?>', $(this).serialize(), function(res) {
            if (res.includes('sukses')) {
                Swal.fire('Sukses', 'Libur berhasil disimpan', 'success');
                $('#modal-libur').modal('hide');
                table.ajax.reload();
            } else {
                Swal.fire('Error', 'Gagal menyimpan', 'error');
            }
        });
    });
});
</script>
