<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Master Level Siswa</h4>
                    <button class="btn btn-primary btn-round ml-auto btn-add-level" data-toggle="modal">
                        <i class="fa fa-plus mr-2"></i> Tambah Level
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="level-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Level</th>
                                <th>Deskripsi</th>
                                <th>Urutan</th>
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
        <div class="modal fade" id="modal-level" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-level">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Level</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_level">
                            <input type="hidden" name="tipe_form" value="add">
                            
                            <div class="form-group">
                                <label>Nama Level <span class="text-danger">*</span></label>
                                <input type="text" name="nama_level" class="form-control" required placeholder="Contoh: Beginner, Intermediate, Advanced">
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="2" placeholder="Deskripsi level..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Urutan Level <span class="text-danger">*</span></label>
                                <input type="number" name="urutan_level" class="form-control" required min="1" placeholder="Urutan dari terendah ke tertinggi">
                                <small class="text-muted">Urutan menentukan hirarki level (1 = paling dasar)</small>
                            </div>
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" required>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Non Aktif</option>
                                </select>
                                <small class="text-muted">Level non aktif tidak bisa dipilih saat update level siswa</small>
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

        <script>
            $(document).ready(function() {
                var table = $('#level-table').DataTable({
                    "ajax": {
                        url: '<?php echo site_url("MasterData/level_siswa_page"); ?>',
                        type: 'POST'
                    },
                    "columnDefs": [{
                        "targets": -1,
                        "render": function(data, type, row) {
                            return `<center>
                                <button class='btn btn-link btn-primary btn-edit-level' title="Edit" data-id="${row[0]}">
                                    <i class='fa fa-edit'></i>
                                </button>
                                <button class='btn btn-link btn-danger btn-hapus-level' title="Hapus" data-id="${row[0]}">
                                    <i class='fa fa-times'></i>
                                </button>
                            </center>`;
                        }
                    }],
                    "order": [[3, 'asc']]
                });

                $('.btn-add-level').click(function() {
                    $('#label_tipe').text('Tambah');
                    $('#form-level')[0].reset();
                    $("input[name='tipe_form']").val('add');
                    $("input[name='id_level']").val('');
                    $('#modal-level').modal('show');
                });

                $(document).on('click', '.btn-edit-level', function() {
                    var id = $(this).data('id');
                    $.post('<?= site_url("MasterData/get_level_siswa") ?>', {id_level: id}, function(res) {
                        var data = JSON.parse(res);
                        $('#label_tipe').text('Edit');
                        $("input[name='tipe_form']").val('edit');
                        $("input[name='id_level']").val(data.id_level);
                        $("input[name='nama_level']").val(data.nama_level);
                        $("textarea[name='deskripsi']").val(data.deskripsi);
                        $("input[name='urutan_level']").val(data.urutan_level);
                        $("select[name='status']").val(data.status);
                        $('#modal-level').modal('show');
                    });
                });

                $(document).on('click', '.btn-hapus-level', function() {
                    var id = $(this).data('id');
                    Swal.fire({
                        title: 'Yakin hapus level ini?',
                        text: 'Level yang sudah digunakan tidak bisa dihapus',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.value) {
                            $.post('<?= site_url("MasterData/hapus_level_siswa") ?>', {id_level: id}, function(res) {
                                var data = JSON.parse(res);
                                if (data.status === 'success') {
                                    Swal.fire('Berhasil', 'Level berhasil dihapus', 'success');
                                    table.ajax.reload();
                                } else {
                                    Swal.fire('Gagal', data.message || 'Gagal menghapus level', 'error');
                                }
                            });
                        }
                    });
                });

                $('#form-level').submit(function(e) {
                    e.preventDefault();
                    $.post('<?= site_url("MasterData/add_level_siswa") ?>', $(this).serialize(), function(res) {
                        if (res.includes('sukses')) {
                            Swal.fire('Sukses', 'Data berhasil disimpan', 'success');
                            $('#modal-level').modal('hide');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', 'Gagal menyimpan data', 'error');
                        }
                    });
                });
            });
        </script>
    </div>
</div>
