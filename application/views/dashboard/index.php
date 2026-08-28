<style>
    .stat-card {
        border-radius: 12px;
        padding: 20px;
        color: white;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .stat-card .stat-value {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-card .stat-label {
        font-size: 14px;
        opacity: 0.9;
    }
    .stat-card .stat-date {
        font-size: 12px;
        opacity: 0.8;
        margin-top: 10px;
    }
    .stat-card .stat-icon {
        position: absolute;
        right: 20px;
        top: 15px;
        font-size: 24px;
        opacity: 0.5;
    }
    .card-blue { background: linear-gradient(135deg, #3498db, #2980b9); }
    .card-green { background: linear-gradient(135deg, #27ae60, #1e8449); }
    .card-orange { background: linear-gradient(135deg, #e67e22, #d35400); }
    .card-red { background: linear-gradient(135deg, #e74c3c, #c0392b); }
    .card-purple { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
    
    .table-dashboard th {
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }
    .table-dashboard td {
        vertical-align: middle;
        font-size: 13px;
    }
    .year-filter {
        display: inline-block;
        padding: 5px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: white;
        font-size: 13px;
    }
    .chart-container {
        position: relative;
        height: 200px;
        width: 100%;
    }
</style>

<?php 
    // Determine active period (default to current month/year if not filtered)
    $bulan_aktif = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
    $tahun_aktif = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
    $periode_label = date('F Y', mktime(0, 0, 0, $bulan_aktif, 1, $tahun_aktif));
?>

<div class="panel-header bg-primary-gradient">
    <div class="page-inner py-4">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
            <div>
                <h3 class="text-white pb-2 fw-bold">Dashboard</h3>
                <p class="text-white op-7 mb-0">Periode: <span class="badge badge-light"><?= $periode_label ?></span></p>
            </div>
            <div class="ml-md-auto py-2 py-md-0">
                <form class="form-inline" method="get">
                    <select name="bulan" class="form-control mr-2">
                        <?php for($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $bulan_aktif == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    <select name="tahun" class="form-control mr-2">
                        <?php for($y = date('Y')-2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $tahun_aktif == $y ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-light">Filter</button>
                </form>
            </div>
        </div>
    </div>
</div>
<br>
<br>
<div class="page-inner mt--5">
    <!-- 5 STAT CARDS -->
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card card-blue position-relative">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-value"><?= $total_peserta ?></div>
                <div class="stat-label">Siswa Aktif</div>
                <div class="stat-date"><i class="fas fa-clock"></i> Per <?= date('d F Y') ?></div>
            </div>
        </div> 
        <div class="col-md-3">
            <div class="stat-card card-green position-relative">
                <i class="fas fa-chart-line stat-icon"></i>
                <div class="stat-value"><?= $total_peserta_baru ?></div>
                <div class="stat-label">Siswa Baru</div>
                <div class="stat-date"><i class="fas fa-clock"></i> <?= $periode_label ?></div>
            </div>
        </div> 
        <div class="col-md-3">
            <div class="stat-card card-purple position-relative">
                <i class="fas fa-money-bill-wave stat-icon"></i>
                <div class="stat-value">Rp <?= number_format($total_pembayaran, 0, ',', '.') ?></div>
                <div class="stat-label">Total Pemasukan</div>
                <div class="stat-date"><i class="fas fa-calendar"></i> <?= $periode_label ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card <?= $laba_rugi >= 0 ? 'card-green' : 'card-red' ?> position-relative">
                <i class="fas fa-balance-scale stat-icon"></i>
                <div class="stat-value"><?= $laba_rugi >= 0 ? '' : '-' ?>Rp <?= number_format(abs($laba_rugi), 0, ',', '.') ?></div>
                <div class="stat-label">Laba/Rugi</div>
                <div class="stat-date"><i class="fas fa-calendar"></i> <?= $periode_label ?></div>
            </div>
        </div>
    </div>
    
    <!-- Second Row: SPP Belum Dibayar -->
    <div class="row">
        <div class="col-md-4">
            <div class="stat-card card-orange position-relative">
                <i class="fas fa-file-invoice-dollar stat-icon"></i>
                <div class="stat-value">Rp <?= number_format($spp_belum_dibayar, 0, ',', '.') ?></div>
                <div class="stat-label">SPP Belum Dibayar</div>
                <div class="stat-date"><i class="fas fa-calendar"></i> <?= $periode_label ?></div>
            </div>
        </div>
    </div>
    
    <!-- PENDAPATAN & PIE CHART -->
    <div class="row mt-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Rincian Pendapatan</h4>
                    <select class="year-filter" id="filterTahunPendapatan">
                        <?php for($y = date('Y')-2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover table-dashboard mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Jml. Bayar</th>
                                    <th>Tgl. Bayar</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-pendapatan">
                                <?php $no = 1; foreach($rincian_pendapatan as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row->nama_anak ?: $row->nama_ortu ?></td>
                                    <td><?= $row->nama_kelas ?: '-' ?></td>
                                    <td>Rp <?= number_format($row->jumlah, 0, ',', '.') ?></td>
                                    <td><?= $row->tgl_bayar ? date('d-m-Y', strtotime($row->tgl_bayar)) : '-' ?></td>
                                    <td>
                                        <a href="<?= site_url('Trans/tagihan') ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Pendapatan Terbesar</h4>
                    <span class="year-filter"><?= date('Y') ?></span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartPendapatan"></canvas>
                    </div>
                    <div id="legendPendapatan" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PENGELUARAN & PIE CHART -->
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Pengeluaran Terbesar</h4>
                    <span class="year-filter"><?= date('Y') ?></span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartPengeluaran"></canvas>
                    </div>
                    <div id="legendPengeluaran" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Rincian Pengeluaran</h4>
                    <select class="year-filter" id="filterTahunPengeluaran">
                        <?php for($y = date('Y')-2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover table-dashboard mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kategori</th>
                                    <th>Jumlah</th>
                                    <th>Tgl. Pengeluaran</th>
                                    <th>Jenis</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-pengeluaran">
                                <?php $no = 1; foreach($rincian_pengeluaran as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row->kategori ?: '-' ?></td>
                                    <td>Rp <?= number_format($row->jumlah, 0, ',', '.') ?></td>
                                    <td><?= $row->tanggal ? date('d-m-Y', strtotime($row->tanggal)) : '-' ?></td>
                                    <td><span class="badge badge-info"><?= isset($row->metode_bayar) ? $row->metode_bayar : 'Tunai' ?></span></td>
                                    <td>
                                        <a href="<?= site_url('Pengeluaran/index') ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> 

<script>
$(document).ready(function() {
    // Get current year from URL or default
    var urlParams = new URLSearchParams(window.location.search);
    var currentTahun = urlParams.get('tahun') || '<?= date("Y") ?>';
    
    // Set dropdown values from URL
    $('#filterTahunPendapatan').val(currentTahun);
    $('#filterTahunPengeluaran').val(currentTahun);
    
    // Filter year change - reload page with new year
    $('#filterTahunPendapatan, #filterTahunPengeluaran').change(function() {
        var tahun = $(this).val();
        var url = new URL(window.location.href);
        url.searchParams.set('tahun', tahun);
        window.location.href = url.toString();
    });
    
    // PIE CHART PENDAPATAN
    var dataPendapatan = <?= json_encode($pendapatan_kategori) ?>;
    var labelsPendapatan = dataPendapatan.map(item => item.kategori || 'Lainnya');
    var valuesPendapatan = dataPendapatan.map(item => parseInt(item.total || 0));
    var colorsPendapatan = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22'];
    
    if (labelsPendapatan.length > 0) {
        var ctxPendapatan = document.getElementById('chartPendapatan').getContext('2d');
        new Chart(ctxPendapatan, {
            type: 'doughnut',
            data: {
                labels: labelsPendapatan,
                datasets: [{
                    data: valuesPendapatan,
                    backgroundColor: colorsPendapatan.slice(0, labelsPendapatan.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Legend pendapatan
        var legendHtml = '';
        labelsPendapatan.forEach(function(label, i) {
            legendHtml += '<span class="badge mr-2 mb-1" style="background-color:' + colorsPendapatan[i] + ';color:white;">' + label + '</span>';
        });
        $('#legendPendapatan').html(legendHtml);
    } else {
        $('#legendPendapatan').html('<span class="text-muted">Tidak ada data</span>');
    }
    
    // PIE CHART PENGELUARAN
    var dataPengeluaran = <?= json_encode($pengeluaran_kategori) ?>;
    var labelsPengeluaran = dataPengeluaran.map(item => item.kategori || 'Lainnya');
    var valuesPengeluaran = dataPengeluaran.map(item => parseInt(item.total || 0));
    var colorsPengeluaran = ['#e74c3c', '#f39c12', '#3498db', '#2ecc71', '#9b59b6', '#1abc9c', '#34495e', '#e67e22'];
    
    if (labelsPengeluaran.length > 0) {
        var ctxPengeluaran = document.getElementById('chartPengeluaran').getContext('2d');
        new Chart(ctxPengeluaran, {
            type: 'doughnut',
            data: {
                labels: labelsPengeluaran,
                datasets: [{
                    data: valuesPengeluaran,
                    backgroundColor: colorsPengeluaran.slice(0, labelsPengeluaran.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Legend pengeluaran
        var legendPengeluaranHtml = '';
        labelsPengeluaran.forEach(function(label, i) {
            legendPengeluaranHtml += '<span class="badge mr-2 mb-1" style="background-color:' + colorsPengeluaran[i] + ';color:white;">' + label + '</span>';
        });
        $('#legendPengeluaran').html(legendPengeluaranHtml);
    } else {
        $('#legendPengeluaran').html('<span class="text-muted">Tidak ada data</span>');
    }
});
</script>