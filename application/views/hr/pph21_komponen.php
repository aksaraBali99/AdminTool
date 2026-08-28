<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Komponen PPh21</h4>
                    <button class="btn btn-primary btn-round ml-auto" id="btn-add">
                        <i class="fa fa-plus mr-2"></i> Tambah Komponen
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Komponen PPh21 akan digunakan untuk perhitungan pajak otomatis pada payroll
                </div>
                
                <div class="table-responsive">
                    <table id="pph21-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Komponen</th>
                                <th>Batas Bawah</th>
                                <th>Batas Atas</th>
                                <th>Persentase</th>
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
        <div class="modal fade" id="modal-pph21" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-pph21">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Komponen PPh21</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id">
                            <input type="hidden" name="tipe_form" value="add">
                            
                            <div class="form-group">
                                <label>Nama Komponen <span class="text-danger">*</span></label>
                                <input type="text" name="nama_komponen" class="form-control" required placeholder="5%, 15%, 25%, etc">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Batas Bawah (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" name="batas_bawah" class="form-control" required value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Batas Atas (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" name="batas_atas" class="form-control" required value="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Persentase (%) <span class="text-danger">*</span></label>
                                        <input type="number" name="persentase" class="form-control" required step="0.1" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-control" required>
                                            <option value="aktif">Aktif</option>
                                            <option value="nonaktif">Nonaktif</option>
                                        </select>
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
    var table = $('#pph21-table').DataTable({
        "ajax": {
            url: '<?= site_url("Hr/pph21_komponen_page") ?>',
            type: 'POST'
        },
        "columnDefs": [{
            "targets": -1,
            "render": function(data, type, row) {
                return `<center>
                    <button class='btn btn-link btn-primary btn-edit' data-id="${row[0]}" title="Edit">
                        <i class='fa fa-edit'></i>
                    </button>
                </center>`;
            }
        }]
    });

    $('#btn-add').click(function() {
        $('#label_tipe').text('Tambah');
        $('#form-pph21')[0].reset();
        $("input[name='tipe_form']").val('add');
        $('#modal-pph21').modal('show');
    });

    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.post('<?= site_url("Hr/get_pph21") ?>', {id: id}, function(res) {
            var data = JSON.parse(res);
            $('#label_tipe').text('Edit');
            $("input[name='tipe_form']").val('edit');
            $("input[name='id']").val(data.id);
            $("input[name='nama_komponen']").val(data.nama_komponen);
            $("input[name='batas_bawah']").val(data.batas_bawah);
            $("input[name='batas_atas']").val(data.batas_atas);
            $("input[name='persentase']").val(data.persentase);
            $("select[name='status']").val(data.status);
            $('#modal-pph21').modal('show');
        });
    });

    $('#form-pph21').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Hr/add_pph21") ?>', $(this).serialize(), function(res) {
            if (res.includes('sukses')) {
                Swal.fire('Sukses', 'Komponen berhasil disimpan', 'success');
                $('#modal-pph21').modal('hide');
                table.ajax.reload();
            }
        });
    });
});
</script>
