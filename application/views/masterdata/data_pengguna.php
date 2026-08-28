<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Pengelolaan User</h4>
                    <button class="btn btn-primary btn-round ml-auto btn-add-pengguna" data-toggle="modal">
                        <i class="fa fa-plus mr-2"></i>
                        Tambah Pengguna
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pengguna-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Username</th>
                                <th></th>
                                <th>Jabatan</th>
                                <th>Nama</th>
                                <th>Jenis Kelamin</th>
                                <th>No HP</th>
                                <th>
                                    <center>Fungsi</center>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal tambah data -->
<div class="modal fade m-add-pengguna" id="add-pengguna" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="card-title" id="exampleModalLabel"><span id="label_tipe">Tambah</span> User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="tambah-pengguna">
                    <input type="hidden" name="id_user">
                    <div class="form-group">
                        <label for="exampleFormControlInput1">Username</label>
                        <input type="hidden" class="form-control" name="tipe_form" value="add">
                        <input type="text" class="form-control" name="username" placeholder="" required>
                    </div>
                    <div class="form-group">
                        <label for="exampleFormControlInput1">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="" required>
                    </div>
                    <div class="form-group">
                        <label for="exampleFormControlSelect1">Jabatan</label>
                        <select class="form-control" name="jabatan" required>
                            <option value='superadmin'>Super Admin (Full Akses)</option>
                            <option value='admin'>Admin (CRM, Siswa, Pembayaran, Penjadwalan)</option>
                            <option value='finance'>Finance (Keuangan, HR & Payroll)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exampleFormControlInput1">Nama</label>
                        <input type="text" class="form-control" name="nama" placeholder="" required>
                    </div>
                    <div class="form-group">
                        <label for="exampleFormControlSelect1">Jenis Kelamin</label>
                        <select class="form-control" name="jk" required>
                            <option value='L'>Laki-Laki</option>
                            <option value='P'>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exampleFormControlInput1">No HP</label>
                        <input type="text" class="form-control" name="no_hp" placeholder="" required>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan User</button>
            </div>
            </form>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            var role = '<?php echo $_SESSION['jabatan']; ?>';
            var table = $('#pengguna-table').DataTable({
                "ajax": {
                    url: '<?php echo site_url("MasterData/pengguna_page"); ?>',
                    type: 'POST'
                },
                "columnDefs": [{
                        "targets": [0],
                        "visible": false,
                        "searchable": false
                    },
                    {
                        "targets": [2],
                        "visible": false,
                        "searchable": false
                    },
                    {
                        "data": [0],
                        "targets": -1,
                        "render": function(data, type, row, meta) {
                            var btn = '';
                            btn += "<center><button type='button' data-toggle='tooltip' class='btn btn-link btn-primary btn-lg btn-edit-pengguna' data-original-title='Edit Data' id=" + data + "> <i class='fa fa-edit'></i></button></a> <button type='button' data-toggle='tooltip' class='btn btn-link btn-danger btn-lg' data-original-title='Hapus Data' name='hapus' id=" +
                                data + " onclick='hapus(`" + data +
                                "`)'><i class='fa fa-times'></i>  </button>";

                            return btn;
                        }
                    }
                ]
            });

            $('#tambah-pengguna').on('submit', function(event) {
                event.preventDefault();
                $.ajax({
                    type: "POST",
                    url: '<?php echo site_url('MasterData/add_pengguna'); ?>',
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
                            title: 'User Berhasil ditambahkan'
                        });
                        $('#tambah-pengguna').trigger("reset");
                        setTimeout(function() {
                            window.location.href =
                                '<?php echo site_url('MasterData/pengguna'); ?>';
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

            $(document).on('click', '.btn-add-pengguna', function() {
                $(".m-add-pengguna").modal("show");
                $("input[name='password']").prop('required', true);
                $('form')[0].reset()
            });

            $(document).on('click', '.btn-edit-pengguna', function() {
                $(".m-add-pengguna").modal("show");
                $("input[name='tipe_form']").val('edit');
                $("input[name='password']").prop('required', false);

                $.ajax({
                    type: "POST",
                    url: '<?php echo site_url('MasterData/get_pengguna'); ?>',
                    data: {
                        "id_pengguna": $(this).attr('id'),
                    },
                    dataType: "json",
                    success: function(data) {
                        $("#label_tipe").text('Ubah');
                        $("input[name='id_user']").val(data[0].id_user);
                        $("input[name='username']").val(data[0].username);
                        $("input[name='nama']").val(data[0].nama);
                        $("input[name='password']").val('');
                        $("input[name='no_hp']").val(data[0].no_hp);
                        $("select[name='jk']").val(data[0].jk);
                        $("select[name='jabatan']").val(data[0].jabatan);
                    },
                    error: function(request, status, error) {
                        console.log('Gagal ke Server')
                    }
                });
            });
        });

        function hapus(id) {

            var id_user = id;
            Swal.fire({
                title: 'Anda yakin Hapus Pengguna ini..?',
                text: "",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya',
                cancelButtonText: "Tidak",
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        type: "POST",
                        url: '<?php echo site_url('MasterData/hapus_pengguna'); ?>',
                        data: {
                            id_user: id_user
                        },
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
                                title: 'OK, Berhasil Dihapus'
                            });

                            setTimeout(function() {
                                window.location.href =
                                    '<?php echo site_url('MasterData/pengguna'); ?>';
                                window.clearTimeout();
                            }, 1000);

                        },
                        error: function(request, status, error) {
                            console.log('Gagal ke Server')


                        }

                    });
                }
                if (result.dismiss == "cancel") {
                    console.log('batal');
                }

            });

        }
    </script>