<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Laporan Kelas & Jadwal</h4>
                    <div class="ml-auto">
                        <span class="badge badge-primary" style="font-size: 1.2em;" id="total-count">0</span>
                        <span class="ml-1">Total Jadwal</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-table" class="display table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kelas</th>
                                <th>Tipe / Jenis</th>
                                <th>Hari</th>
                                <th>Jam</th>
                                <th>Guru</th>
                                <th>Peserta</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Peserta -->
<div class="modal fade" id="modal-murid" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-users mr-2"></i>Detail Peserta - <span id="modal-kelas-nama"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="table-murid" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Orangtua</th>
                                <th>No HP</th>
                                <th>Status</th>
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
            url: '<?= site_url("Report/get_kelas_jadwal_summary") ?>',
            type: 'GET',
            dataSrc: function(json) {
                $('#total-count').text(json.data.length);
                return json.data;
            }
        },
        "order": [[1, 'asc'], [3, 'asc']]
    });
    
    // Show detail modal
    $(document).on('click', '.btn-detail-murid', function() {
        var id_jadwal = $(this).data('jadwal');
        var nama_kelas = $(this).data('nama');
        
        $('#modal-kelas-nama').text(nama_kelas);
        
        $.get('<?= site_url("Report/get_murid_by_kelas") ?>/' + id_jadwal, function(res) {
            var data = JSON.parse(res);
            var tbody = $('#table-murid tbody');
            tbody.html('');
            
            if (data.length == 0) {
                tbody.html('<tr><td colspan="5" class="text-center text-muted">Tidak ada peserta terdaftar</td></tr>');
            } else {
                var no = 1;
                data.forEach(function(row) {
                    var status_badge = '';
                    if (row.status == 'Registrasi Kelas') {
                        status_badge = '<span class="badge badge-success">Siswa Aktif</span>';
                    } else {
                        status_badge = '<span class="badge badge-warning">' + row.status + '</span>';
                    }
                    
                    tbody.append(`<tr>
                        <td>${no++}</td>
                        <td>${row.nama_anak || '-'}</td>
                        <td>${row.nama_ortu || '-'}</td>
                        <td>${row.no_hp}</td>
                        <td>${status_badge}</td>
                    </tr>`);
                });
            }
            
            $('#modal-murid').modal('show');
        });
    });
});
</script>
