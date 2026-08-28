<div class="page-inner">
    <?php 
        // Determine active period (default to current month/year if not filtered)
        $bulan_aktif = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
        $tahun_aktif = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
        $periode_label = date('F Y', mktime(0, 0, 0, $bulan_aktif, 1, $tahun_aktif));
    ?>
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
        <div>
            <h3 class="text-dark pb-2 fw-bold">Dashboard Finance</h3>
            <h6 class="op-7 mb-2">Selamat Datang, <?= $this->session->userdata('nama') ?> | <span class="badge badge-primary">Periode: <?= $periode_label ?></span></h6>
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
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>
    
    <div class="row mt-3">
        <!-- Card 1: SPP Belum Dibayar -->
        <div class="col-sm-6 col-md-4">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-warning bubble-shadow-small">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="col col-stats ml-3 ml-sm-0">
                            <div class="numbers">
                                <p class="card-category">SPP Belum Dibayar</p>
                                <h4 class="card-title">Rp <?= number_format($spp_belum_dibayar, 0, ',', '.') ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card 2: Laba/Rugi Bulan Ini -->
        <div class="col-sm-6 col-md-4">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <?php if ($laba_rugi >= 0): ?>
                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <?php else: ?>
                            <div class="icon-big text-center icon-danger bubble-shadow-small">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col col-stats ml-3 ml-sm-0">
                            <div class="numbers">
                                <p class="card-category">Laba/Rugi Bulan Ini</p>
                                <?php if ($laba_rugi >= 0): ?>
                                <h4 class="card-title text-success">Rp <?= number_format($laba_rugi, 0, ',', '.') ?></h4>
                                <?php else: ?>
                                <h4 class="card-title text-danger">-Rp <?= number_format(abs($laba_rugi), 0, ',', '.') ?></h4>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card 3: Total Pemasukan -->
        <div class="col-sm-6 col-md-4">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <div class="col col-stats ml-3 ml-sm-0">
                            <div class="numbers">
                                <p class="card-category">Total Pemasukan</p>
                                <h4 class="card-title">Rp <?= number_format($total_pembayaran, 0, ',', '.') ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Perbandingan Pembayaran</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1">Bulan Ini</p>
                            <h4 class="text-success">Rp <?= number_format($total_pembayaran_ini, 0, ',', '.') ?></h4>
                        </div>
                        <div class="col-6">
                            <p class="mb-1">Bulan Lalu</p>
                            <h4 class="text-secondary">Rp <?= number_format($total_pembayaran_lalu, 0, ',', '.') ?></h4>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex align-items-center">
                        <span class="mr-2">Persentase:</span>
                        <?php if ($persentase_pembayaran >= 0): ?>
                        <span class="badge badge-success"><i class="fas fa-arrow-up"></i> <?= $persentase_pembayaran ?>%</span>
                        <?php else: ?>
                        <span class="badge badge-danger"><i class="fas fa-arrow-down"></i> <?= abs($persentase_pembayaran) ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Quick Access</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <a href="<?= site_url('Pengeluaran/index') ?>" class="btn btn-danger btn-block">
                                <i class="fas fa-credit-card"></i> Pengeluaran
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="<?= site_url('Hr/payroll_guru') ?>" class="btn btn-primary btn-block">
                                <i class="fas fa-user-tie"></i> Payroll Guru
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="<?= site_url('Hr/payroll_staff') ?>" class="btn btn-info btn-block">
                                <i class="fas fa-users"></i> Payroll Staff
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="<?= site_url('Hr/laporan_payroll') ?>" class="btn btn-secondary btn-block">
                                <i class="fas fa-file-alt"></i> Laporan Payroll
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
