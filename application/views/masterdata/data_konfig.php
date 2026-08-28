<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <form id="tambah-konfig" enctype="multipart/form-data">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">Pengaturan Sistem</h4>
                        <button class="btn btn-primary btn-round ml-auto btn-add-pengguna" type="submit">
                            <i class="fa fa-save mr-2"></i> Simpan Konfig
                        </button>
                    </div>

                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="exampleFormControlInput1">Nama Aplikasi</label>
                        <input type="hidden" class="form-control" name="id_konfig" value="<?= $konfig[0]->id_konfig; ?>">
                        <input type="text" class="form-control" name="nama_apk" placeholder="" value="<?= $konfig[0]->nama_apk; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="exampleFormControlInput1">Token WA</label>
                        <input type="text" class="form-control" name="token_wa" placeholder="" value="<?= $konfig[0]->token_wa; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Logo Bimbel</label><br>
                        <img src="<?php echo base_url('uploads/logo/') . $konfig[0]->logo; ?>" alt="..." class="avatar-img rounded-circle mb-1" style="width:130px"><br>
                        <?php if (!empty($konfig[0]->logo)) : ?>
                            <small>Logo saat ini: <?= $konfig[0]->logo; ?></small>
                        <?php endif; ?><br>
                        <input type="file" class="form-control-file" name="dokumen">
                    </div>
                </div>
        </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    $('.select2_instance').select2();
    setTimeout(() => {

        $('.select2_instance').trigger('change');
    }, 400);

    $(document).ready(function() {
        $('#tambah-konfig').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                type: "POST",
                url: '<?php echo site_url('MasterData/add_konfig'); ?>',
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function(data) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-right',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    Toast.fire({
                        type: 'success',
                        title: 'Data Berhasil Disimpan'
                    });
                    $('#tambah-konfig').trigger("reset");
                    setTimeout(function() {
                        window.location.href =
                            '<?php echo site_url('MasterData/konfig'); ?>';
                        window.clearTimeout();
                    }, 1000);

                },
                error: function(request, status, error) {
                    console.log(request.responseText);
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-right',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    Toast.fire({
                        type: 'error',
                        title: 'Gagal menghubungkan Ke Server'
                    })
                }

            });

        });

    });
</script>