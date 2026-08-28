<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Pengelolaan Pengajar</h4>
                    <button class="btn btn-primary btn-round ml-auto btn-add-pengajar" data-toggle="modal">
                        <i class="fa fa-plus mr-2"></i> Tambah Pengajar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pengajar-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>JK</th>
                                <th>No HP</th>
                                <th>Alamat</th>
                                <th>No Rekening</th>
                                <th>Jadwal</th>
                                <th>
                                    <center>Aksi</center>
                                </th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
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

<!-- Modal tambah/edit pengajar -->
<div class="modal fade m-add-pengajar" id="add-pengajar" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="form-pengajar">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="label_tipe">Tambah</span> Pengajar</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pengajar">
                    <input type="hidden" name="tipe_form" value="add">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Jenis Kelamin</label>
                            <select name="jk" class="form-control" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>No HP</label>
                            <input type="text" name="no_hp" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>No Rekening</label>
                            <input type="text" name="no_rek" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tarif/Jam (Anak) <span class="text-danger">*</span></label>
                            <input type="number" name="tarif_per_jam_anak" class="form-control" placeholder="50000" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tarif/Jam (Dewasa) <span class="text-danger">*</span></label>
                            <input type="number" name="tarif_per_jam_dewasa" class="form-control" placeholder="75000" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Biaya Transport <span class="text-danger">*</span></label>
                            <input type="number" name="biaya_transport" class="form-control" placeholder="25000" required>
                        </div>
                    </div>
                    <!-- <div class="form-group">
                        <label>Jadwal Mengajar (Multi Hari & Jam)</label>
                        <div id="jadwal-container"></div>
                        <button type="button" class="btn btn-sm btn-success" id="add-jadwal"><i class="fa fa-plus"></i> Tambah Jadwal</button>
                    </div> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pengajar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script -->
<script type="text/javascript">
    $(document).ready(function() {
        var table = $('#pengajar-table').DataTable({
            "ajax": {
                url: '<?php echo site_url("MasterData/pengajar_page"); ?>',
                type: 'POST'
            },
            "columnDefs": [{
                "data": null,
                "targets": -1,
                "render": function(data, type, row) {
                    return `<center>
                    <button class='btn btn-link btn-primary btn-edit-pengajar' id="${row[0]}"><i class='fa fa-edit'></i></button>
                    <button class='btn btn-link btn-danger' onclick="hapusPengajar('${row[0]}')"><i class='fa fa-times'></i></button>
                </center>`;
                }
            }]
        });

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

        function tambahJadwalRow(day = '', start = '', end = '', id = '') {
            $('#jadwal-container').append(`
        <div class="form-row mb-2 jadwal-row">
            <input type="hidden" name="id_jadwal_pengajar[]" value="${id}">
            <div class="col-md-3">
                <select name="hari[]" class="form-control" required>
                    <option value="">--Hari--</option>
                    <option value="1">Senin</option>
                    <option value="2">Selasa</option>
                    <option value="3">Rabu</option>
                    <option value="4">Kamis</option>
                    <option value="5">Jumat</option>
                    <option value="6">Sabtu</option>
                    <option value="7">Minggu</option>
                </select>
            </div>
            <div class="col-md-3"><input type="time" name="jam_mulai[]" class="form-control" value="${start}" required></div>
            <div class="col-md-3"><input type="time" name="jam_selesai[]" class="form-control" value="${end}" required></div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger btn-sm remove-jadwal" onclick=><i class="fa fa-trash"></i></button>
            </div>
        </div>
    `);
            $('#jadwal-container').find('select:last').val(day);
        }


        $(document).on('click', '#add-jadwal', function() {
            tambahJadwalRow();
        });

        $(document).on('click', '.remove-jadwal', function() {
            let id = $(this).closest('.jadwal-row').find('input[name="id_jadwal_pengajar[]"]').val();
            var a = $(this);
            Swal.fire({
                title: 'Yakin hapus jadwal ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.value) {
                    $.post('<?php echo site_url("MasterData/hapus_jadwal"); ?>', {
                        id_jadwal_pengajar: id
                    }, function() {
                        Swal.fire("Sukses", "Data berhasil disimpan", "success");
                        a.closest('.jadwal-row').remove();

                    });
                }
            });

        });

        $(document).on('click', '.btn-add-pengajar', function() {
            $('#label_tipe').text('Tambah');
            $('#form-pengajar')[0].reset();
            $("input[name='tipe_form']").val('add');
            $('#jadwal-container').html('');
            tambahJadwalRow(); // default satu jadwal
            $('#add-pengajar').modal('show');
        });

        $(document).on('click', '.btn-edit-pengajar', function() {
            $.ajax({
                type: "POST",
                url: '<?php echo site_url("MasterData/get_pengajar"); ?>',
                data: {
                    id_pengajar: $(this).attr('id')
                },
                dataType: "json",
                success: function(res) {
                    $('#label_tipe').text('Ubah');
                    $("input[name='tipe_form']").val('edit');
                    $("input[name='id_pengajar']").val(res.pengajar.id_pengajar);
                    $("input[name='nama']").val(res.pengajar.nama);
                    $("select[name='jk']").val(res.pengajar.jk);
                    $("input[name='no_hp']").val(res.pengajar.no_hp);
                    $("input[name='no_rek']").val(res.pengajar.no_rek);
                    $("textarea[name='alamat']").val(res.pengajar.alamat);
                    $("input[name='tarif_per_jam_anak']").val(res.pengajar.tarif_per_jam_anak);
                    $("input[name='tarif_per_jam_dewasa']").val(res.pengajar.tarif_per_jam_dewasa);
                    $("input[name='biaya_transport']").val(res.pengajar.biaya_transport);

                    $('#jadwal-container').html('');
                    res.jadwal.forEach(j => {
                        tambahJadwalRow(j.hari, j.jam_mulai, j.jam_selesai, j.id_jadwal_pengajar);
                    });

                    $('#add-pengajar').modal('show');
                }
            });
        });

        $('#form-pengajar').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: '<?php echo site_url("MasterData/add_pengajar"); ?>',
                data: $(this).serialize(),
                dataType: "json",
                success: function() {
                    Swal.fire("Sukses", "Data berhasil disimpan", "success");
                    $('#add-pengajar').modal('hide');
                    table.ajax.reload();
                },
                error: function() {
                    Swal.fire("Error", "Gagal menghubungkan server", "error");
                }
            });
        });
    });

    function hapusPengajar(id) {
        Swal.fire({
            title: 'Yakin hapus data ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                $.post('<?php echo site_url("MasterData/hapus_pengajar"); ?>', {
                    id_pengajar: id
                }, function() {
                    Swal.fire('Berhasil', 'Data dihapus', 'success');
                    $('#pengajar-table').DataTable().ajax.reload();
                });
            }
        });
    }
</script>