<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Penempatan Guru</h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-add">
                        <i class="fa fa-plus mr-2"></i> Tambah Penempatan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="penempatan-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Guru</th>
                                <th>Kelas</th>
                                <th>Tgl Mulai</th>
                                <th>Tgl Selesai</th>
                                <th>Status</th>
                                <th><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="modal-penempatan" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-penempatan">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Penempatan Guru</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id">
                            <input type="hidden" name="tipe_form" value="add">
                            
                            <div class="form-group">
                                <label>Guru <span class="text-danger">*</span></label>
                                <select name="id_guru" class="form-control" required>
                                    <option value="">-- Pilih Guru --</option>
                                    <?php foreach($pengajar as $p): ?>
                                    <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Kelas <span class="text-danger">*</span></label>
                                <select name="id_kelas" class="form-control" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach($jenis_kelas as $jk): ?>
                                    <option value="<?= $jk->id_jenis_kelas ?>"><?= $jk->nama_kelas ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Mulai <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_mulai" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Selesai</label>
                                        <input type="date" name="tanggal_selesai" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" required>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                                    <option value="Diberhentikan">Diberhentikan</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="catatan" class="form-control" rows="2"></textarea>
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
    var table = $('#penempatan-table').DataTable({
        "ajax": {
            url: '<?= site_url("Penjadwalan/penempatan_guru_page") ?>',
            type: 'POST'
        },
        "columnDefs": [{
            "targets": -1,
            "render": function(data, type, row) {
                return `<center>
                    <button class='btn btn-link btn-primary btn-edit' data-id="${row[0]}" title="Edit">
                        <i class='fa fa-edit'></i>
                    </button>
                    <button class='btn btn-link btn-danger btn-hapus' data-id="${row[0]}" title="Hapus">
                        <i class='fa fa-trash'></i>
                    </button>
                </center>`;
            }
        }]
    });

    $(document).on('click', '.btn-hapus', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Penempatan?',
            text: 'Data penempatan guru akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Penjadwalan/hapus_penempatan") ?>', {id: id}, function(res) {
                    if (res.includes('sukses')) {
                        Swal.fire('Berhasil', 'Penempatan dihapus', 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', 'Gagal menghapus', 'error');
                    }
                });
            }
        });
    });

    $('#btn-add').click(function() {
        $('#label_tipe').text('Tambah');
        $('#form-penempatan')[0].reset();
        $("input[name='tipe_form']").val('add');
        $('#modal-penempatan').modal('show');
    });

    $('#form-penempatan').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Penjadwalan/add_penempatan") ?>', $(this).serialize(), function(res) {
            if (res.includes('sukses')) {
                Swal.fire('Sukses', 'Penempatan berhasil disimpan', 'success');
                $('#modal-penempatan').modal('hide');
                table.ajax.reload();
            } else {
                Swal.fire('Error', 'Gagal menyimpan', 'error');
            }
        });
    });

    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.post('<?= site_url("Penjadwalan/get_penempatan") ?>', {id: id}, function(res) {
            var data = JSON.parse(res);
            $('#label_tipe').text('Edit');
            $("input[name='tipe_form']").val('edit');
            $("input[name='id']").val(data.id);
            $("select[name='id_guru']").val(data.id_guru);
            $("select[name='id_kelas']").val(data.id_kelas);
            $("input[name='tanggal_mulai']").val(data.tanggal_mulai);
            $("input[name='tanggal_selesai']").val(data.tanggal_selesai);
            $("select[name='status']").val(data.status);
            $("textarea[name='catatan']").val(data.catatan);
            $('#modal-penempatan').modal('show');
        });
    });
});
</script>
