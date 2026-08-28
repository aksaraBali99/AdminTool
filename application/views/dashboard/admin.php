<?php 
    // Determine active period (default to current month/year if not filtered)
    $bulan_aktif = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
    $tahun_aktif = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
    $periode_label = date('F Y', mktime(0, 0, 0, $bulan_aktif, 1, $tahun_aktif));
?>
<div class="page-inner">
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
        <div>
            <h3 class="text-dark pb-2 fw-bold">Dashboard Admin</h3>
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
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="col col-stats ml-3 ml-sm-0">
                            <div class="numbers">
                                <p class="card-category">Siswa Aktif</p>
                                <h4 class="card-title"><?= $total_peserta ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="col col-stats ml-3 ml-sm-0">
                            <div class="numbers">
                                <p class="card-category">Siswa Baru</p>
                                <h4 class="card-title"><?= $total_peserta_baru ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                        <div class="col col-stats ml-3 ml-sm-0">
                            <div class="numbers">
                                <p class="card-category">Murid Trial</p>
                                <h4 class="card-title"><?= $total_murid_trial ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-danger bubble-shadow-small">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                        <div class="col col-stats ml-3 ml-sm-0">
                            <div class="numbers">
                                <p class="card-category">SPP Belum Bayar</p>
                                <h4 class="card-title">Rp <?= number_format($spp_belum_dibayar, 0, ',', '.') ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class="col-sm-6 col-md-4">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-warning bubble-shadow-small">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                        <div class="col col-stats ml-3 ml-sm-0">
                            <div class="numbers">
                                <p class="card-category">Total Kelas</p>
                                <h4 class="card-title"><?= $total_kelas ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Quick Access</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="<?= site_url('Crm/lead') ?>" class="btn btn-info btn-block btn-lg">
                                <i class="fas fa-user-plus"></i> CRM Lead
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= site_url('Peserta/peserta') ?>" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-users"></i> List Siswa
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= site_url('Trans/tagihan') ?>" class="btn btn-success btn-block btn-lg">
                                <i class="fas fa-file-invoice-dollar"></i> Tagihan
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= site_url('Penjadwalan/calendar') ?>" class="btn btn-warning btn-block btn-lg">
                                <i class="fas fa-calendar-alt"></i> Kalender
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Dashboard Admin Custom</h4>
                    <p class="text-muted">Dashboard khusus admin akan diimplementasikan sesuai kebutuhan.</p>
                </div>
                <div class="card-body">
                    <p class="text-center text-muted">
                        <i class="fas fa-tools fa-3x mb-3"></i><br>
                        Silakan hubungi admin untuk kustomisasi dashboard ini.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
