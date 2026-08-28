<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Data Karyawan</h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-add">
                        <i class="fa fa-plus mr-2"></i> Tambah Karyawan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <select id="filter_status" class="form-control">
                            <option value="">-- Semua Status --</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Dipecat">Dipecat</option>
                            <option value="Mengundurkan Diri">Resign</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filter_jenis" class="form-control">
                            <option value="">-- Semua Jenis --</option>
                            <option value="Full Time">Full Time</option>
                            <option value="Part Time">Part Time</option>
                            <option value="Freelance">Freelance</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-info" id="btn-filter">
                            <i class="fa fa-search"></i> Filter
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="karyawan-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Posisi</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Mulai Kerja</th>
                                <th>Dok</th>
                                <th><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div class="modal fade" id="modal-karyawan" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="form-karyawan">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Karyawan</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_karyawan">
                            <input type="hidden" name="tipe_form" value="add">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" name="nama" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>No. KTP</label>
                                        <input type="text" name="no_ktp" class="form-control" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>No. Telp</label>
                                        <input type="text" name="no_telp" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NPWP</label>
                                        <input type="text" name="npwp" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Alamat</label>
                                <textarea name="alamat" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status Pernikahan</label>
                                        <select name="status_ktp" class="form-control">
                                            <option value="Belum Menikah">Belum Menikah</option>
                                            <option value="Menikah">Menikah</option>
                                            <option value="Cerai">Cerai</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Posisi</label>
                                        <input type="text" name="posisi" class="form-control" placeholder="Guru, Admin, dll">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Jenis Karyawan <span class="text-danger">*</span></label>
                                        <select name="jenis_karyawan" class="form-control" required>
                                            <option value="Full Time">Full Time</option>
                                            <option value="Part Time">Part Time</option>
                                            <option value="Freelance">Freelance</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tanggal Mulai Kerja <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_mulai_kerja" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status Karyawan <span class="text-danger">*</span></label>
                                        <select name="status_karyawan" class="form-control" required>
                                            <option value="Aktif">Aktif</option>
                                            <option value="Dipecat">Dipecat</option>
                                            <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tanggal Keluar</label>
                                        <input type="date" name="tanggal_keluar" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            <h6>Gaji (untuk Staff)</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Gaji Pokok</label>
                                        <input type="number" name="gaji_pokok" class="form-control" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tunjangan</label>
                                        <input type="number" name="tunjangan" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            <h6>Link ke Data Pengajar (opsional)</h6>
                            <div class="form-group">
                                <label>Pilih Pengajar</label>
                                <select name="id_pengajar" class="form-control">
                                    <option value="">-- Tidak ada --</option>
                                    <?php foreach ($pengajar as $p): ?>
                                    <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Jika karyawan ini adalah guru, hubungkan dengan data pengajar</small>
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
    var table = $('#karyawan-table').DataTable({
        "ajax": {
            url: '<?= site_url("Hr/karyawan_page") ?>',
            type: 'POST',
            data: function(d) {
                d.status_karyawan = $('#filter_status').val();
                d.jenis_karyawan = $('#filter_jenis').val();
            }
        },
        "columnDefs": [{
            "targets": -1,
            "render": function(data, type, row) {
                return `<center>
                    <button class='btn btn-link btn-primary btn-edit' data-id="${row[0]}" title="Edit">
                        <i class='fa fa-edit'></i>
                    </button>
                    <a href='<?= site_url("Hr/dokumen") ?>/${row[0]}' class='btn btn-link btn-info' title="Dokumen">
                        <i class='fa fa-folder'></i>
                    </a>
                    <button class='btn btn-link btn-danger btn-hapus' data-id="${row[0]}" title="Hapus">
                        <i class='fa fa-times'></i>
                    </button>
                </center>`;
            }
        }]
    });

    $('#btn-filter').click(function() { table.ajax.reload(); });

    $('#btn-add').click(function() {
        $('#label_tipe').text('Tambah');
        $('#form-karyawan')[0].reset();
        $("input[name='tipe_form']").val('add');
        $('#modal-karyawan').modal('show');
    });

    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.post('<?= site_url("Hr/get_karyawan") ?>', {id_karyawan: id}, function(res) {
            var data = JSON.parse(res);
            $('#label_tipe').text('Edit');
            $("input[name='tipe_form']").val('edit');
            $("input[name='id_karyawan']").val(data.id_karyawan);
            $("input[name='nama']").val(data.nama);
            $("input[name='no_ktp']").val(data.no_ktp);
            $("input[name='no_telp']").val(data.no_telp);
            $("input[name='npwp']").val(data.npwp);
            $("textarea[name='alamat']").val(data.alamat);
            $("select[name='status_ktp']").val(data.status_ktp);
            $("input[name='posisi']").val(data.posisi);
            $("select[name='jenis_karyawan']").val(data.jenis_karyawan);
            $("input[name='tanggal_mulai_kerja']").val(data.tanggal_mulai_kerja);
            $("select[name='status_karyawan']").val(data.status_karyawan);
            $("input[name='tanggal_keluar']").val(data.tanggal_keluar);
            $("input[name='gaji_pokok']").val(data.gaji_pokok);
            $("input[name='tunjangan']").val(data.tunjangan);
            $('#modal-karyawan').modal('show');
        });
    });

    $(document).on('click', '.btn-hapus', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus/resign karyawan ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Hr/hapus_karyawan") ?>', {id_karyawan: id}, function(res) {
                    if (res.includes('sukses')) {
                        Swal.fire('Berhasil', 'Karyawan diubah statusnya', 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    });

    $('#form-karyawan').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Hr/add_karyawan") ?>', $(this).serialize(), function(res) {
            if (res.includes('sukses')) {
                Swal.fire('Sukses', 'Data berhasil disimpan', 'success');
                $('#modal-karyawan').modal('hide');
                table.ajax.reload();
            }
        });
    });
});
</script>
