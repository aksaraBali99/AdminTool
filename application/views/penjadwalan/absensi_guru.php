<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Absensi Guru</h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-add">
                        <i class="fa fa-plus mr-2"></i> Input Absensi
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <input type="date" id="filter_dari" class="form-control" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="filter_sampai" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
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
                    <table id="absensi-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Nama Guru</th>
                                <th>Jam</th>
                                <th>Total</th>
                                <th>Jumlah Kedatangan</th>
                                <th>Kelas</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div class="modal fade" id="modal-absensi" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="form-absensi">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Input</span> Absensi Guru</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id">
                            <input type="hidden" name="tipe_form" value="add">
                            
                            <div class="row">
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
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
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status Hadir <span class="text-danger">*</span></label>
                                        <select name="status_hadir" class="form-control" required>
                                            <option value="Hadir">Hadir</option>
                                            <option value="Izin">Izin</option>
                                            <option value="Alpha">Alpha</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tipe Kelas <span class="text-danger">*</span></label>
                                        <select name="tipe_kelas" class="form-control" required>
                                            <option value="anak">Kelas Anak</option>
                                            <option value="dewasa">Kelas Dewasa</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jumlah Kedatangan <span class="text-danger">*</span></label>
                                        <input type="number" name="jumlah_kedatangan" class="form-control" value="1" min="1" max="10" required>
                                        <small class="text-muted">Jumlah kali kedatangan dalam 1 hari (untuk perhitungan transport)</small>
                                    </div>
                                </div>
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
    var table = $('#absensi-table').DataTable({
        "ajax": {
            url: '<?= site_url("Penjadwalan/absensi_guru_page") ?>',
            type: 'POST',
            data: function(d) {
                d.tanggal_dari = $('#filter_dari').val();
                d.tanggal_sampai = $('#filter_sampai').val();
                d.id_guru = $('#filter_guru').val();
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
        "order": [[1, 'desc']]
    });

    $('#btn-filter').click(function() {
        table.ajax.reload();
    });

    $('#btn-add').click(function() {
        $('#label_tipe').text('Input');
        $('#form-absensi')[0].reset();
        $("input[name='tipe_form']").val('add');
        $("input[name='tanggal']").val('<?= date('Y-m-d') ?>');
        $('#modal-absensi').modal('show');
    });

    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.post('<?= site_url("Penjadwalan/get_absensi_guru_by_id") ?>', {id: id}, function(res) {
            var data = JSON.parse(res);
            $('#label_tipe').text('Edit');
            $("input[name='tipe_form']").val('edit');
            $("input[name='id']").val(data.id);
            $("select[name='id_guru']").val(data.id_guru);
            $("input[name='tanggal']").val(data.tanggal);
            $("input[name='jam_mulai']").val(data.jam_mulai);
            $("input[name='jam_selesai']").val(data.jam_selesai);
            $("select[name='status_hadir']").val(data.status_hadir);
            $("select[name='tipe_kelas']").val(data.tipe_kelas || 'anak');
            $("input[name='jumlah_kedatangan']").val(data.jumlah_kedatangan || 1);
            $('#modal-absensi').modal('show');
        });
    });

    $(document).on('click', '.btn-hapus', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus absensi ini?',
            text: 'Data akan di-soft delete',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Penjadwalan/hapus_absensi_guru") ?>', {id: id}, function(res) {
                    if (res.includes('sukses')) {
                        Swal.fire('Berhasil', 'Absensi dihapus', 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    });

    $('#form-absensi').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Penjadwalan/add_absensi_guru") ?>', $(this).serialize(), function(res) {
            try {
                var data = JSON.parse(res);
                if (data.status == 'error') {
                    Swal.fire('Error', data.message, 'error');
                    return;
                }
            } catch(e) {}
            
            if (res.includes('sukses')) {
                Swal.fire('Sukses', 'Absensi berhasil disimpan', 'success');
                $('#modal-absensi').modal('hide');
                table.ajax.reload();
            } else {
                Swal.fire('Error', 'Gagal menyimpan', 'error');
            }
        });
    });
});
</script>
