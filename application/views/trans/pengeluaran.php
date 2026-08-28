<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Pengelolaan Pengeluaran</h4>
                    <button class="btn btn-primary btn-round ml-auto btn-add-pengeluaran" data-toggle="modal">
                        <i class="fa fa-plus mr-2"></i> Tambah Pengeluaran
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pengeluaran-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>
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

<!-- Modal tambah/edit pengeluaran -->
<div class="modal fade m-add-pengeluaran" id="add-pengeluaran" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="form-pengeluaran">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="label_tipe">Tambah</span> Pengeluaran</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pengeluaran">
                    <input type="hidden" name="tipe_form" value="add">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Kategori</label>
                            <select name="kategori" class="form-control" required>
                                <option value="Fix">Fix</option>
                                <option value="Variable">Variable</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Jumlah</label>
                            <input type="number" name="jumlah" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var table = $('#pengeluaran-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= site_url('Pengeluaran/pengeluaran_page') ?>',
                type: 'POST',
                data: function(d) {
                    d.bulan = $('#filterBulan').val();
                    d.tahun = $('#filterTahun').val();
                }
            },
            "columnDefs": [{
                "data": null,
                "targets": -1,
                "render": function(data, type, row) {
                    var btn = '';
                    var wa_web = 'https://wa.me/' +
                        row[3];
                    btn += `<center>
                    <button class='btn btn-link btn-primary btn-edit-pengeluaran' id="${row[5]}"><i class='fa fa-edit'></i></button>
                    <button class='btn btn-link btn-danger' onclick="hapusPengeluaran('${row[5]}')"><i class='fa fa-times'></i></button>`;

                    return btn;
                }
            }]
        });

        $('#filterBulan, #filterTahun').on('change', function() {
            table.draw();
        });

        $(document).on('click', '.btn-add-pengeluaran', function() {
            $('#label_tipe').text('Tambah');
            $('#form-pengeluaran')[0].reset();
            $("input[name='tipe_form']").val('add');
            $('#add-pengeluaran').modal('show');
        });

        $(document).on('click', '.btn-edit-pengeluaran', function() {
            $.ajax({
                type: "POST",
                url: '<?php echo site_url("Pengeluaran/get_pengeluaran"); ?>',
                data: {
                    id_pengeluaran: $(this).attr('id')
                },
                dataType: "json",
                success: function(res) {
                    $('#label_tipe').text('Ubah');
                    $("input[name='tipe_form']").val('edit');
                    $("input[name='id_pengeluaran']").val(res[0].id_pengeluaran);
                    $("input[name='tanggal']").val(res[0].tanggal);
                    $("select[name='kategori']").val(res[0].kategori);
                    $("input[name='keterangan']").val(res[0].keterangan);
                    $("input[name='jumlah']").val(parseInt(res[0].jumlah));
                    $('#add-pengeluaran').modal('show');
                }
            });
        });

        $('#form-pengeluaran').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: '<?php echo site_url("Pengeluaran/add_pengeluaran"); ?>',
                data: $(this).serialize(),
                dataType: "json",
                success: function() {
                    Swal.fire("Sukses", "Data berhasil disimpan", "success");
                    $('#add-pengeluaran').modal('hide');
                    table.ajax.reload();
                },
                error: function() {
                    Swal.fire("Error", "Gagal menghubungkan server", "error");
                }
            });
        });
    });

    function hapusPengeluaran(id) {
        Swal.fire({
            title: 'Yakin hapus data ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                $.post('<?php echo site_url("Pengeluaran/hapus_pengeluaran"); ?>', {
                    id_pengeluaran: id
                }, function() {
                    Swal.fire('Berhasil', 'Data dihapus', 'success');
                    $('#pengeluaran-table').DataTable().ajax.reload();
                });
            }
        });
    }
</script>