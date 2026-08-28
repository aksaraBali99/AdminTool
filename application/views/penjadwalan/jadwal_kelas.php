<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Jadwal Kelas</h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-add">
                        <i class="fa fa-plus mr-2"></i> Tambah Jadwal
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select id="filter_guru" class="form-control">
                            <option value="">-- Semua Guru --</option>
                            <?php foreach($pengajar as $p): ?>
                            <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-info btn-block" id="btn-filter">
                            <i class="fa fa-search"></i> Filter
                        </button>
                    </div>
                </div> 
                
                <div class="table-responsive">
                    <table id="jadwal-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hari</th>
                                <th>Jam</th>
                                <th>Kelas</th>
                                <th>Guru</th>
                                <th>Ruangan</th>
                                <th>Tipe</th>
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
        <div class="modal fade" id="modal-jadwal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="form-jadwal">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Jadwal Kelas</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id">
                            <input type="hidden" name="tipe_form" value="add">
                            
                            <div id="bentrok-alert" class="alert alert-danger" style="display:none;"></div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Kelas <span class="text-danger">*</span></label>
                                        <select name="id_kelas" class="form-control" required>
                                            <option value="">-- Pilih Kelas --</option>
                                            <?php foreach($jenis_kelas as $jk): ?>
                                            <option value="<?= $jk->id_jenis_kelas ?>"><?= $jk->nama_kelas ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Guru <span class="text-danger">*</span></label>
                                        <select name="id_guru" class="form-control" required>
                                            <option value="">-- Pilih Guru --</option>
                                            <?php foreach($pengajar as $p): ?>
                                            <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Hari <span class="text-danger">*</span></label>
                                        <select name="hari" class="form-control" required>
                                            <option value="1">Senin</option>
                                            <option value="2">Selasa</option>
                                            <option value="3">Rabu</option>
                                            <option value="4">Kamis</option>
                                            <option value="5">Jumat</option>
                                            <option value="6">Sabtu</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Jam Mulai <span class="text-danger">*</span></label>
                                        <input type="time" name="jam_mulai" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Jam Selesai <span class="text-danger">*</span></label>
                                        <input type="time" name="jam_selesai" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Ruangan</label>
                                        <input type="text" name="ruangan" class="form-control" placeholder="Ruang A, B, C...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tipe Kelas <span class="text-danger">*</span></label>
                                        <select name="tipe_kelas" class="form-control" required>
                                            <option value="anak">Kelas Anak</option>
                                            <option value="dewasa">Kelas Dewasa</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jenis Jadwal <span class="text-danger">*</span></label>
                                        <select name="jenis_jadwal" class="form-control" required>
                                            <option value="Regular">Regular</option>
                                            <option value="Trial Class">Trial Class</option>
                                            <option value="Placement Test">Placement Test</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Max Pertemuan/Bulan</label>
                                        <input type="number" name="max_pertemuan_bulan" class="form-control" value="8" min="1">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan atau informasi tambahan (opsional)"></textarea>
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
    var table = $('#jadwal-table').DataTable({
        "ajax": {
            url: '<?= site_url("Penjadwalan/jadwal_kelas_page") ?>',
            type: 'POST',
            data: function(d) {
                d.id_guru = $('#filter_guru').val();
            }
        },
        "columnDefs": [{
            "targets": -1,
            "render": function(data, type, row) {
                return `<center>
                    <button class='btn btn-sm btn-primary btn-edit' data-id="${row[0]}"><i class='fa fa-edit'></i></button>
                    <button class='btn btn-sm btn-danger btn-hapus' data-id="${row[0]}"><i class='fa fa-trash'></i></button>
                </center>`;
            }
        }],
        "order": [[1, 'asc'], [2, 'asc']]
    });

    $('#btn-filter').click(function() {
        table.ajax.reload();
    });

    $('#btn-add').click(function() {
        $('#label_tipe').text('Tambah');
        $('#form-jadwal')[0].reset();
        $("input[name='tipe_form']").val('add');
        $('#bentrok-alert').hide();
        $('#modal-jadwal').modal('show');
    });

    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.post('<?= site_url("Penjadwalan/get_jadwal_kelas_by_id") ?>', {id: id}, function(res) {
            var data = JSON.parse(res);
            $('#label_tipe').text('Edit');
            $("input[name='tipe_form']").val('edit');
            $("input[name='id']").val(data.id);
            $("select[name='id_kelas']").val(data.id_kelas);
            $("select[name='id_guru']").val(data.id_guru);
            $("select[name='hari']").val(data.hari);
            $("input[name='jam_mulai']").val(data.jam_mulai);
            $("input[name='jam_selesai']").val(data.jam_selesai);
            $("input[name='ruangan']").val(data.ruangan);
            $("select[name='tipe_kelas']").val(data.tipe_kelas);
            $("select[name='jenis_jadwal']").val(data.jenis_jadwal || 'Regular');
            $("textarea[name='keterangan']").val(data.keterangan || '');
            $("input[name='max_pertemuan_bulan']").val(data.max_pertemuan_bulan);
            $('#bentrok-alert').hide();
            $('#modal-jadwal').modal('show');
        });
    });

    $(document).on('click', '.btn-hapus', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Jadwal?',
            text: 'Data akan dihapus permanen',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Penjadwalan/hapus_jadwal_kelas") ?>', {id: id}, function(res) {
                    if (res.includes('sukses')) {
                        Swal.fire('Berhasil', 'Jadwal dihapus', 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    });

    $('#form-jadwal').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Penjadwalan/add_jadwal_kelas") ?>', $(this).serialize(), function(res) {
            var data = JSON.parse(res);
            if (data.status == 'success') {
                Swal.fire('Sukses', 'Jadwal berhasil disimpan', 'success');
                $('#modal-jadwal').modal('hide');
                table.ajax.reload();
            } else {
                $('#bentrok-alert').html('<i class="fa fa-exclamation-triangle"></i> ' + data.message).show();
            }
        });
    });
});
</script>
