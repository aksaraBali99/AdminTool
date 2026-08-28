<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Reschedule Kelas</h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-add">
                        <i class="fa fa-plus mr-2"></i> Tambah Reschedule
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reschedule-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kelas / Guru</th>
                                <th>Jadwal Lama</th>
                                <th>Jadwal Baru</th>
                                <th>Alasan</th>
                                <th><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Reschedule -->
        <div class="modal fade" id="modal-reschedule" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="form-reschedule">
                        <div class="modal-header">
                            <h5 class="modal-title">Reschedule Kelas</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Pilih Jadwal <span class="text-danger">*</span></label>
                                <select name="id_jadwal_kelas" class="form-control" required>
                                    <option value="">-- Pilih Jadwal --</option>
                                    <?php 
                                    $hari = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                                    foreach($jadwal_kelas as $jk): ?>
                                    <option value="<?= $jk->id ?>"><?= $jk->nama_kelas ?> - <?= $jk->nama_pengajar ?> (<?= $hari[$jk->hari] ?>, <?= substr($jk->jam_mulai, 0, 5) ?>-<?= substr($jk->jam_selesai, 0, 5) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Tanggal Lama (yang akan dipindah) <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_lama" class="form-control" required>
                            </div>
                            
                            <hr>
                            <h6>Jadwal Baru:</h6>
                            
                            <div class="form-group">
                                <label>Tanggal Baru <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_baru" class="form-control" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jam Mulai Baru <span class="text-danger">*</span></label>
                                        <input type="time" name="jam_baru_mulai" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jam Selesai Baru <span class="text-danger">*</span></label>
                                        <input type="time" name="jam_baru_selesai" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Alasan Reschedule <span class="text-danger">*</span></label>
                                <textarea name="alasan" class="form-control" rows="2" required placeholder="Guru berhalangan, libur mendadak, dll..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Keterangan Tambahan</label>
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
    var table = $('#reschedule-table').DataTable({
        "ajax": {
            url: '<?= site_url("Penjadwalan/reschedule_page") ?>',
            type: 'POST'
        },
        "columnDefs": [{
            "targets": -1,
            "render": function(data, type, row) {
                return `<center>
                    <button class='btn btn-sm btn-danger btn-hapus' data-id="${row[0]}" title="Hapus">
                        <i class='fa fa-trash'></i>
                    </button>
                </center>`;
            }
        }],
        "order": [[0, 'desc']]
    });

    $('#btn-add').click(function() {
        $('#form-reschedule')[0].reset();
        $('#modal-reschedule').modal('show');
    });

    $('#form-reschedule').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Penjadwalan/add_reschedule") ?>', $(this).serialize(), function(res) {
            if (res.includes('sukses')) {
                Swal.fire('Sukses', 'Reschedule berhasil disimpan', 'success');
                $('#modal-reschedule').modal('hide');
                table.ajax.reload();
            } else {
                Swal.fire('Error', 'Gagal menyimpan reschedule', 'error');
            }
        });
    });

    $(document).on('click', '.btn-hapus', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Reschedule?',
            text: 'Data reschedule akan dihapus permanen',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Penjadwalan/hapus_reschedule") ?>', {id: id}, function(res) {
                    if (res.includes('sukses')) {
                        Swal.fire('Berhasil', 'Reschedule dihapus', 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    });
});
</script>
