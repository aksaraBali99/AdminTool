<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Laporan Data Lead</h4>
                    <div class="ml-auto">
                        <span class="badge badge-info" style="font-size: 1.2em;" id="total-count">0</span>
                        <span class="ml-1">Total Lead</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Filter Status</label>
                        <select id="filter_status" class="form-control">
                            <option value="">-- Semua Status --</option>
                            <option value="Info Harga">Info Harga</option>
                            <option value="Jadwal Trial">Jadwal Trial</option>
                            <option value="Placement Test">Placement Test</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-info btn-block" id="btn-filter"><i class="fa fa-search"></i> Filter</button>
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="mb-3">
                    <small class="text-muted">
                        Keterangan Warna Tgl Dihubungi: 
                        <span class="badge badge-success">≤ 3 hari</span>
                        <span class="badge badge-warning">4-7 hari</span>
                        <span class="badge badge-danger">> 7 hari</span>
                        <span class="badge badge-secondary">Belum pernah</span>
                    </small>
                </div>
                
                <div class="table-responsive">
                    <table id="data-table" class="display table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anak</th>
                                <th>Nama Orangtua</th>
                                <th>No HP</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Tgl Dihubungi</th>
                                <th>Jenis</th>
                                <th>Sumber</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#data-table').DataTable({
        "ajax": {
            url: '<?= site_url("Report/get_data_lead") ?>',
            type: 'POST',
            data: function(d) {
                d.status = $('#filter_status').val();
            },
            dataSrc: function(json) {
                $('#total-count').text(json.data.length);
                return json.data;
            }
        },
        "order": [[6, 'desc']],
        "columnDefs": [
            { "orderable": false, "targets": [0] }
        ]
    });
    
    $('#btn-filter').click(function() {
        table.ajax.reload();
    });
});
</script>
