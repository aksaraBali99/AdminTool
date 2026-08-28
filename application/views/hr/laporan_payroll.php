<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Laporan Payroll</h4>
                    <button class="btn btn-danger ml-auto" id="btn-pdf" disabled>
                        <i class="fa fa-file-pdf mr-1"></i> Export PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <label>Bulan</label>
                        <select id="filter_bulan" class="form-control">
                            <?php 
                            $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= date('n') == $i ? 'selected' : '' ?>><?= $bulan_nama[$i] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Tahun</label>
                        <select id="filter_tahun" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block" id="btn-generate">
                            <i class="fa fa-chart-bar"></i> Generate
                        </button>
                    </div>
                </div>
                
                <div id="result-container" style="display:none;">
                    <hr>
                    
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Total Payroll Guru</h6>
                                    <h3 id="total-guru">Rp 0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Total Payroll Staff</h6>
                                    <h3 id="total-staff">Rp 0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h6>Grand Total</h6>
                                    <h3 id="grand-total">Rp 0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payroll Guru -->
                    <h5 class="mb-3"><i class="fa fa-chalkboard-teacher"></i> Payroll Guru</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered" id="table-guru">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Guru</th>
                                    <th class="text-center">Jam Ajar</th>
                                    <th class="text-right">Honor</th>
                                    <th class="text-right">Transport</th>
                                    <th class="text-right">PPh21</th>
                                    <th class="text-right">Gaji Bersih</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    
                    <!-- Payroll Staff -->
                    <h5 class="mb-3"><i class="fa fa-users"></i> Payroll Staff</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="table-staff">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th class="text-right">Gaji Pokok</th>
                                    <th class="text-right">Tunjangan</th>
                                    <th class="text-right">Potongan</th>
                                    <th class="text-right">PPh21</th>
                                    <th class="text-right">Gaji Bersih</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="no-data" class="text-center py-5" style="display:none;">
                    <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada data payroll untuk periode ini</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function formatRupiah(angka) {
        return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
    }
    
    $('#btn-generate').click(function() {
        var bulan = $('#filter_bulan').val();
        var tahun = $('#filter_tahun').val();
        
        $.post('<?= site_url("Hr/generate_laporan") ?>', {bulan: bulan, tahun: tahun}, function(res) {
            var data = JSON.parse(res);
            
            if (data.guru.length == 0 && data.staff.length == 0) {
                $('#result-container').hide();
                $('#no-data').show();
                $('#btn-pdf').prop('disabled', true);
                return;
            }
            
            $('#no-data').hide();
            $('#result-container').show();
            $('#btn-pdf').prop('disabled', false);
            
            // Summary
            $('#total-guru').text(formatRupiah(data.summary.total_guru));
            $('#total-staff').text(formatRupiah(data.summary.total_staff));
            $('#grand-total').text(formatRupiah(data.summary.grand_total));
            
            // Table Guru
            var tbodyGuru = $('#table-guru tbody');
            tbodyGuru.html('');
            var no = 1;
            data.guru.forEach(function(g) {
                var statusBadge = g.status == 'paid' ? '<span class="badge badge-primary">Paid</span>' : 
                                  (g.status == 'approved' ? '<span class="badge badge-success">Approved</span>' : '<span class="badge badge-secondary">Draft</span>');
                var tr = `<tr>
                    <td>${no++}</td>
                    <td>${g.nama_guru}</td>
                    <td class="text-center">${parseFloat(g.total_jam_ajar_anak) + parseFloat(g.total_jam_ajar_dewasa)} jam</td>
                    <td class="text-right">${formatRupiah(g.total_honor)}</td>
                    <td class="text-right">${formatRupiah(g.total_transport)}</td>
                    <td class="text-right">${formatRupiah(g.pph21_nominal)}</td>
                    <td class="text-right font-weight-bold">${formatRupiah(g.total_gaji_bersih)}</td>
                    <td class="text-center">${statusBadge}</td>
                </tr>`;
                tbodyGuru.append(tr);
            });
            
            // Table Staff
            var tbodyStaff = $('#table-staff tbody');
            tbodyStaff.html('');
            no = 1;
            data.staff.forEach(function(s) {
                var statusBadge = s.status == 'paid' ? '<span class="badge badge-primary">Paid</span>' : 
                                  (s.status == 'approved' ? '<span class="badge badge-success">Approved</span>' : '<span class="badge badge-secondary">Draft</span>');
                var tr = `<tr>
                    <td>${no++}</td>
                    <td>${s.nama_karyawan}</td>
                    <td class="text-right">${formatRupiah(s.gaji_pokok)}</td>
                    <td class="text-right">${formatRupiah(s.tunjangan)}</td>
                    <td class="text-right">${formatRupiah(s.potongan)}</td>
                    <td class="text-right">${formatRupiah(s.pph21_nominal)}</td>
                    <td class="text-right font-weight-bold">${formatRupiah(s.total_gaji_bersih)}</td>
                    <td class="text-center">${statusBadge}</td>
                </tr>`;
                tbodyStaff.append(tr);
            });
        });
    });
    
    $('#btn-pdf').click(function() {
        var bulan = $('#filter_bulan').val();
        var tahun = $('#filter_tahun').val();
        window.location.href = '<?= site_url("Hr/export_laporan_pdf") ?>?bulan=' + bulan + '&tahun=' + tahun;
    });
});
</script>
