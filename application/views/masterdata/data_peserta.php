<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Pengelolaan Siswa</h4>
                    <button class="btn btn-primary btn-round ml-auto btn-add-peserta" data-toggle="modal">
                        <i class="fa fa-plus mr-2"></i> Tambah Siswa
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="peserta-table" class="display table table-striped table-hover">
                        <thead>
                            <?php if ($ajax_url == site_url('Crm/lead_page')) {  ?>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Ortu</th>
                                    <th>No HP</th>
                                    <th>Nama Anak</th>
                                    <th>Tgl Lahir</th>
                                    <th>Email</th>
                                    <th>Sumber</th>
                                    <th>Jenis Kelas</th>
                                    <th>Jadwal</th>
                                    <th>Status</th>
                                    <th>
                                        <center>Aksi</center>
                                    </th>
                                </tr>
                            <?php } else { ?>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Anak</th>
                                    <th>Umur</th>
                                    <th>Level Sekolah</th>
                                    <th>Nama Sekolah</th>
                                    <th>Level Siswa</th>
                                    <th>
                                        <center>Aksi</center>
                                    </th>
                                </tr>
                            <?php } ?>

                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Jadwal</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table id="jadwalDetailTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Detail Jadwal</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal tambah/edit peserta -->
        <div class="modal fade m-add-peserta" id="add-peserta" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="form-peserta">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Siswa</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_peserta">
                            <input type="hidden" name="tipe_form" value="add">

                            <!-- Data Orang Tua -->
                            <h6 class="mb-3"><strong>Data Orang Tua</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Nama Orang Tua</label>
                                    <input type="text" name="nama_ortu" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>No HP</label>
                                    <input type="text" name="no_hp" class="form-control" placeholder="Format : 628xxxx" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Alamat Orang Tua</label>
                                    <input type="text" name="alamat_ortu" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>

                            <hr>
                            <!-- Data Anak -->
                            <h6 class="mb-3"><strong>Data Anak</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Nama Anak</label>
                                    <input type="text" name="nama_anak" class="form-control" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Jenis Kelamin</label>
                                    <select name="jk" class="form-control" required>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Tanggal Lahir</label>
                                    <input type="date" name="tgl_lahir_anak" class="form-control">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Level Sekolah</label>
                                    <select name="level_sekolah" class="form-control">
                                        <option value="">-- Pilih Level Sekolah --</option>
                                        <option value="TK">TK</option>
                                        <option value="SD">SD</option>
                                        <option value="SMP">SMP</option>
                                        <option value="SMA">SMA</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Nama Sekolah</label>
                                    <input type="text" name="nama_sekolah" class="form-control" placeholder="Nama sekolah anak">
                                </div>
                            </div>

                            <hr>
                            <!-- Data Kelas & Status -->
                            <h6 class="mb-3"><strong>Data Kelas & Status</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Jenis Kelas</label>
                                    <select name="id_jenis_kelas" class="form-control" required>
                                        <option value="">--Pilih Kelas--</option>
                                        <?php foreach ($jenis_kelas as $jk) : ?>
                                            <option value="<?= $jk->id_jenis_kelas ?>"><?= $jk->nama_kelas ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Sumber</label>
                                    <select name="src" class="form-control">
                                        <option value="">--Pilih Sumber--</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="Dekat Rumah">Dekat Rumah</option>
                                        <option value="Google Maps">Google Maps</option>
                                        <option value="Google">Google</option>
                                        <option value="Dari Teman">Dari Teman</option>
                                        <option value="Dari Saudara">Dari Saudara</option>
                                        <option value="TikTok">TikTok</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">--Pilih Status--</option>
                                        <option value="Info Harga">Info Harga</option>
                                        <option value="Jadwal Trial">Jadwal Trial</option>
                                        <option value="Placement Test">Placement Test</option>
                                        <option value="Registrasi Kelas">Registrasi Kelas</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Tgl Terakhir Dihubungi</label>
                                    <input type="date" name="tgl_terakhir_dihubungi" class="form-control">
                                </div>
                                <div class="form-group col-md-8">
                                    <label>Catatan</label>
                                    <textarea name="catatan" class="form-control" rows="1"></textarea>
                                </div>
                            </div>
                            <hr>
                            <!-- Jadwal -->
                            <h6 class="mb-3"><strong>Jadwal</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <div id="jadwal-container"></div>
                                    <button type="button" class="btn btn-sm btn-success" id="add-jadwal"><i class="fa fa-plus"></i> Tambah Jadwal</button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Siswa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            $(document).ready(function() {

                const jadwalData = '<?php echo json_encode($all_jadwal); ?>';
                var table = $('#peserta-table').DataTable({
                    "ajax": {
                        url: "<?= $ajax_url ?>",
                        type: 'POST'
                    },
                    "columnDefs": [{
                        "data": null,
                        "targets": -1,
                        "render": function(data, type, row) {
                            var btn = '';
                            var wa_web = 'https://wa.me/' + row[2]; // no_hp is at index 2
                            btn += `<center>
                    <a class='btn btn-link btn-info' href='<?= site_url("Siswa/detail/") ?>${row[0]}' title="Detail Siswa"><i class='fa fa-eye'></i></a>
                    <button class='btn btn-link btn-primary btn-edit-peserta' title="Ubah Siswa" id="${row[0]}"><i class='fa fa-edit'></i></button>
                    <button class='btn btn-link btn-danger' onclick="hapusSiswa('${row[0]}')" title="Hapus Siswa"><i class='fa fa-times'></i></button>`;
                            if (row[10] == '1') { // is_aktif at last data index
                                btn += `<button class='btn btn-link btn-primary btn-set-status' fungsi="Non Aktifkan" aktif="0" title="Set Non Aktif" id="${row[0]}"><i class='fa fa-user-slash'></i></button>`;
                            } else {
                                btn += `<button class='btn btn-link btn-primary btn-set-status' fungsi="Aktifkan" title="Set Aktif" aktif="1" id="${row[0]}"><i class='fa fa-user-check'></i></button>`;
                            }
                            btn += `<a class='btn btn-link btn-primary' title="Kirim Pesan Whatsapp"  href="${wa_web}" target="__blank"><i class='fa fa-comment-dots'></i></a>`;
                            btn += ` <button class='btn btn-link btn-primary btn-kirim-invoice' tipe="Biaya Kelas" title="Kirim Tagihan Kelas Siswa"   id="${row[0]}"><i class='fa fa-share-square'></i></button>`;

                      
                            return btn;
                        }
                    }]
                });

                function tambahJadwalRow(selectedId = '') {
                    let options = '<option value="">--Pilih Jadwal Pengajar--</option>';

                    JSON.parse(jadwalData).forEach(j => {
                        const selected = j.id_jadwal_pengajar == selectedId ? 'selected' : '';
                        options += `<option value="${j.id_jadwal_pengajar}" ${selected}>${j.nama_pengajar} - ${j.hari}, ${j.jam_mulai} - ${j.jam_selesai}</option>`;
                    });

                    const html = `
    <div class="form-row mb-2 jadwal-row">
        <div class="col-md-9">
            <select name="id_jadwal_pengajar[]" class="form-control">
                ${options}
            </select>
        </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-danger btn-sm remove-jadwal">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>`;

                    $('#jadwal-container').append(html);
                }

                function lihatDetail(data) {
                    var html = '';
                    var no = 1;
                    var result_data = data.split('@');
                    result_data.forEach(function(r) {
                        html += '<tr>';
                        html += '<td>' + r + '</td>';
                        html += '</tr>';
                    });
                    $('#jadwalDetailTable tbody').html(html);
                    $('#detailModal').modal('show');
                }

                $(document).on('click', '.btn-jadwal-detail', function() {
                    var jadwal = $(this).data('jadwal');
                    lihatDetail(jadwal);
                });

                $(document).on('click', '#add-jadwal', function() {
                    tambahJadwalRow();
                });

                $(document).on('click', '.remove-jadwal', function() {
                    $(this).closest('.jadwal-row').remove();
                });

                $(document).on('click', '.btn-add-peserta', function() {
                    $('#label_tipe').text('Tambah');
                    $('#form-peserta')[0].reset();
                    $("input[name='tipe_form']").val('add');
                    $('#jadwal-container').html('');
                    tambahJadwalRow();
                    $('#add-peserta').modal('show');
                });

                $(document).on('click', '.btn-edit-peserta', function() {
                    $.ajax({
                        type: "POST",
                        url: '<?php echo site_url("Peserta/get_peserta"); ?>',
                        data: {
                            id_peserta: $(this).attr('id')
                        },
                        dataType: "json",
                        success: function(res) {
                            $('#label_tipe').text('Ubah');
                            $("input[name='tipe_form']").val('edit');
                            $("input[name='id_peserta']").val(res.peserta.id_peserta);
                            $("input[name='nama_ortu']").val(res.peserta.nama_ortu);
                            $("input[name='no_hp']").val(res.peserta.no_hp);
                            $("input[name='alamat_ortu']").val(res.peserta.alamat_ortu);
                            $("input[name='email']").val(res.peserta.email);
                            $("input[name='nama_anak']").val(res.peserta.nama_anak);
                            $("select[name='jk']").val(res.peserta.jk);
                            $("input[name='tgl_lahir_anak']").val(res.peserta.tgl_lahir_anak);
                            $("input[name='alamat_anak']").val(res.peserta.alamat_anak);
                            $("select[name='id_jenis_kelas']").val(res.peserta.id_jenis_kelas);
                            $("select[name='src']").val(res.peserta.src);
                            $("select[name='status']").val(res.peserta.status);
                            $("input[name='tgl_terakhir_dihubungi']").val(res.peserta.tgl_terakhir_dihubungi);
                            $("select[name='level_sekolah']").val(res.peserta.level_sekolah);
                            $("input[name='nama_sekolah']").val(res.peserta.nama_sekolah);
                            $("textarea[name='catatan']").val(res.peserta.catatan);

                            $('#jadwal-container').html('');
                            res.jadwal.forEach(j => tambahJadwalRow(j.id_jadwal_pengajar));
                            $('#add-peserta').modal('show');
                        }
                    });
                });

                $(document).on('click', '.btn-set-status', function() {
                    Swal.fire({
                        title: 'Yakin ' + $(this).attr('fungsi') + ' Siswa ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                type: "POST",
                                url: '<?php echo site_url("Peserta/set_status"); ?>',
                                data: {
                                    id_peserta: $(this).attr('id'),
                                    aktif: $(this).attr('aktif')
                                },
                                dataType: "json",
                                success: function(res) {
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-right',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                    Toast.fire({
                                        type: 'success',
                                        title: 'Update Status Siswa Berhasil'
                                    });

                                    table.ajax.reload(null, false);
                                }
                            });
                        }
                    });
                });

                $(document).on('click', '.btn-kirim-invoice', function() {
                    Swal.fire({
                        title: 'Yakin kirim invoice ke Siswa ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                type: "POST",
                                url: '<?php echo site_url("Peserta/kirim_invoice"); ?>',
                                data: {
                                    id_peserta: $(this).attr('id'),
                                },
                                dataType: "json",
                                success: function(res) {
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-right',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                    Toast.fire({
                                        type: 'success',
                                        title: 'Invoice Berhasil dikirim'
                                    });

                                    table.ajax.reload(null, false);
                                }
                            });
                        }
                    });
                });

                $('#form-peserta').on('submit', function(e) {
                    e.preventDefault();
                    $.ajax({
                        type: "POST",
                        url: '<?php echo site_url("Peserta/add_peserta"); ?>',
                        data: $(this).serialize(),
                        dataType: "json",
                        success: function() {
                            Swal.fire("Sukses", "Data berhasil disimpan", "success");
                            $('#add-peserta').modal('hide');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("Error", "Gagal menghubungkan server", "error");
                        }
                    });
                });
            });

            function hapusSiswa(id) {
                Swal.fire({
                    title: 'Yakin hapus data ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.value) {
                        $.post('<?php echo site_url("Peserta/hapus_peserta"); ?>', {
                            id_peserta: id
                        }, function() {
                            Swal.fire('Berhasil', 'Data dihapus', 'success');
                            $('#peserta-table').DataTable().ajax.reload();
                        });
                    }
                });
            }
        </script>