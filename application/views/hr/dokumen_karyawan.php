<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">
                        <a href="<?= site_url('Hr/karyawan') ?>" class="text-primary"><i class="fa fa-arrow-left"></i></a>
                        Dokumen Karyawan: <?= $karyawan->nama ?>
                    </h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-upload">
                        <i class="fa fa-upload mr-2"></i> Upload Dokumen
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr><td width="150"><strong>NIK</strong></td><td>: <?= $karyawan->no_ktp ?: '-' ?></td></tr>
                            <tr><td><strong>Posisi</strong></td><td>: <?= $karyawan->posisi ?: '-' ?></td></tr>
                            <tr><td><strong>Status</strong></td><td>: <?= $karyawan->status_karyawan ?></td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Jenis Dokumen</th>
                                <th>Nama File</th>
                                <th>Tanggal Upload</th>
                                <th><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($dokumen as $d): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge badge-info"><?= $d->jenis_dokumen ?></span></td>
                                <td><?= $d->nama_file ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($d->uploaded_at)) ?></td>
                                <td>
                                    <center>
                                        <a href="<?= site_url('Hr/download_dokumen/' . $d->id) ?>" class="btn btn-sm btn-success" title="Download">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger btn-hapus" data-id="<?= $d->id ?>">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </center>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dokumen)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Belum ada dokumen</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Upload -->
        <div class="modal fade" id="modal-upload" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-upload" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Upload Dokumen</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_karyawan" value="<?= $karyawan->id_karyawan ?>">
                            
                            <div class="form-group">
                                <label>Jenis Dokumen <span class="text-danger">*</span></label>
                                <select name="jenis_dokumen" class="form-control" required>
                                    <option value="KTP">KTP</option>
                                    <option value="NPWP">NPWP</option>
                                    <option value="Offer Letter">Offer Letter</option>
                                    <option value="Kontrak">Kontrak</option>
                                    <option value="Resign">Surat Resign</option>
                                    <option value="BPJS">BPJS</option>
                                    <option value="Ijazah">Ijazah</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>File <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control-file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <small class="text-muted">Format: PDF, JPG, PNG, DOC, DOCX. Max 5MB</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#btn-upload').click(function() {
        $('#modal-upload').modal('show');
    });

    $('#form-upload').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        $.ajax({
            url: '<?= site_url("Hr/upload_dokumen") ?>',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                var data = JSON.parse(res);
                if (data.status == 'success') {
                    Swal.fire('Sukses', data.message, 'success').then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        });
    });

    $('.btn-hapus').click(function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus dokumen ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Hr/hapus_dokumen") ?>', {id: id}, function(res) {
                    if (res.includes('sukses')) {
                        Swal.fire('Berhasil', 'Dokumen dihapus', 'success').then(function() {
                            location.reload();
                        });
                    }
                });
            }
        });
    });
});
</script>
