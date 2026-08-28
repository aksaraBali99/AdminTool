<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Laporan Absensi Guru</h4>
                    <div class="ml-auto">
                        <button class="btn btn-success" id="btn-excel" disabled>
                            <i class="fa fa-file-excel mr-1"></i> Export Excel
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <label>Dari Tanggal</label>
                        <input type="date" id="filter_dari" class="form-control" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Sampai Tanggal</label>
                        <input type="date" id="filter_sampai" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Guru</label>
                        <select id="filter_guru" class="form-control">
                            <option value="">-- Semua Guru --</option>
                            <?php foreach($pengajar as $p): ?>
                            <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block" id="btn-generate">
                            <i class="fa fa-search"></i> Tampilkan
                        </button>
                    </div>
                </div>
                
                <!-- Results -->
                <div id="result-container" style="display: none;">
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="absensi-table">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Guru</th>
                                    <th>Jam Mulai</th>
                                    <th>Jam Selesai</th>
                                    <th class="text-center">Total Jam</th>
                                    <th class="text-center">Kedatangan</th>
                                    <th>Tipe Kelas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr class="bg-light font-weight-bold">
                                    <td colspan="5" class="text-right">TOTAL:</td>
                                    <td class="text-center" id="total-jam">0 jam</td>
                                    <td class="text-center" id="total-kedatangan">0</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <div id="no-data" class="text-center py-5" style="display: none;">
                    <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada data absensi untuk periode ini</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#btn-generate').click(function() {
        var dari = $('#filter_dari').val();
        var sampai = $('#filter_sampai').val();
        var id_guru = $('#filter_guru').val();
        
        $.post('<?= site_url("Penjadwalan/get_laporan_absensi") ?>', {
            tanggal_dari: dari,
            tanggal_sampai: sampai,
            id_guru: id_guru
        }, function(res) {
            var data = JSON.parse(res);
            var tbody = $('#absensi-table tbody');
            tbody.html('');
            
            if (data.length == 0) {
                $('#result-container').hide();
                $('#no-data').show();
                $('#btn-excel').prop('disabled', true);
                return;
            }
            
            $('#no-data').hide();
            $('#result-container').show();
            $('#btn-excel').prop('disabled', false);
            
            var totalJam = 0;
            var totalKedatangan = 0;
            var no = 1;
            
            data.forEach(function(row) {
                totalJam += parseFloat(row.total_jam);
                totalKedatangan += parseInt(row.jumlah_kedatangan || 1);
                
                var statusBadge = '';
                if (row.status_hadir == 'Hadir') {
                    statusBadge = '<span class="badge badge-success">Hadir</span>';
                } else if (row.status_hadir == 'Izin') {
                    statusBadge = '<span class="badge badge-warning">Izin</span>';
                } else {
                    statusBadge = '<span class="badge badge-danger">Alpha</span>';
                }
                
                var tipeBadge = row.tipe_kelas == 'dewasa' 
                    ? '<span class="badge badge-info">Dewasa</span>' 
                    : '<span class="badge badge-primary">Anak</span>';
                
                var tr = `<tr>
                    <td>${no++}</td>
                    <td>${row.tanggal_format}</td>
                    <td>${row.nama_pengajar}</td>
                    <td>${row.jam_mulai}</td>
                    <td>${row.jam_selesai}</td>
                    <td class="text-center">${row.total_jam} jam</td>
                    <td class="text-center">${row.jumlah_kedatangan || 1}x</td>
                    <td>${tipeBadge}</td>
                    <td>${statusBadge}</td>
                </tr>`;
                tbody.append(tr);
            });
            
            $('#total-jam').text(totalJam + ' jam');
            $('#total-kedatangan').text(totalKedatangan + 'x');
        });
    });
    
    $('#btn-excel').click(function() {
        var dari = $('#filter_dari').val();
        var sampai = $('#filter_sampai').val();
        var id_guru = $('#filter_guru').val();
        
        window.location.href = '<?= site_url("Penjadwalan/export_absensi_excel") ?>?dari=' + dari + '&sampai=' + sampai + '&id_guru=' + id_guru;
    });
});
</script>
