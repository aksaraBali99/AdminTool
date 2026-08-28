<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title> <?php echo $this->apk[0]->nama_apk; ?></title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <script src="<?php echo base_url('assets/js/plugin/webfont/webfont.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/plugin/webfont/webfont.min.js'); ?>"></script>
    <link rel="icon" type="image/x-icon" href="<?php echo base_url(); ?>assets/images/favicon.ico" />


    <script>
        WebFont.load({
            google: {
                "families": ["Lato:300,400,700,900"]
            },
            custom: {
                "families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                urls: ['<?php echo base_url("assets/css/fonts.min.css"); ?>']
            },
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->

    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/atlantis.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/sweetalert2.css') ?>">
    <script src="<?php echo base_url('assets/js/core/jquery.3.2.1.min.js'); ?>"></script>

    <!-- Chart Circle -->
    <script src="<?php echo base_url('assets/js/plugin/chart-circle/circles.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/core/popper.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/core/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/select2.js') ?>"></script>
    <!-- jQuery UI -->
    <script src="<?php echo base_url('assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js'); ?>"></script>

    <!-- jQuery Scrollbar -->
    <script src="<?php echo base_url('assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js'); ?>"></script>


    <!-- Chart JS -->
    <script src="<?php echo base_url('assets/js/plugin/chart.js/chart.min.js'); ?>"></script>

    <!-- jQuery Sparkline -->
    <script src="<?php echo base_url('assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js'); ?>"></script>


    <!-- Datatables -->
    <script src="<?php echo base_url('assets/js/plugin/datatables/datatables.min.js'); ?>"></script>

    <!-- Bootstrap Notify -->
    <script src="<?php echo base_url('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js'); ?>"></script>

    <!-- jQuery Vector Maps -->
    <script src="<?php echo base_url('assets/js/plugin/jqvmap/jquery.vmap.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/plugin/jqvmap/maps/jquery.vmap.world.js'); ?>"></script>

    <!-- Sweet Alert -->
    <script src="<?php echo base_url('assets/js/sweetalert2.js') ?>"></script>

    <!-- Atlantis JS -->
    <script src="<?php echo base_url('assets/js/atlantis.min.js'); ?>"></script>

    <script>
        $('.js-example-basic-single').select2();
    </script>

    <style>
        .swal-wide {
            width: 250px !important;
        }
    </style>

</head>

<body>
    <div class="wrapper">
        <div class="main-header">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="blue">

                <a href="#" class="logo text-light">
                    <?php echo $this->apk[0]->nama_apk; ?>
                </a>
                <button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse" data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon">
                        <i class="icon-menu"></i>
                    </span>
                </button>
                <button class="topbar-toggler more"><i class="icon-options-vertical"></i></button>
                <div class="nav-toggle">
                    <button class="btn btn-toggle toggle-sidebar">
                        <i class="icon-menu"></i>
                    </button>
                </div>
            </div>
            <!-- End Logo Header -->

            <!-- Navbar Header -->
            <nav class="navbar navbar-header navbar-expand-lg" data-background-color="blue2">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
                        <li class="nav-item dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm">
                                    <img src="<?php echo base_url('uploads/logo/' . $this->apk[0]->logo); ?>" alt="..." class="avatar-img rounded-circle">
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li>
                                        <div class="user-box">
                                            <div class="avatar-lg"><img src="<?php echo base_url('uploads/logo/' . $this->apk[0]->logo); ?>" alt="image profile" class="avatar-img rounded"></div>
                                            <div class="u-text">
                                                <h4><?php echo ucfirst($_SESSION['username']); ?></h4>
                                                <p class="text-muted"><?php echo ucfirst($_SESSION['jabatan']); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                    <div class="dropdown-divider"></div>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo site_url('Login/logout'); ?>">Logout</a>
                                    </li>
                                </div>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- End Navbar -->
        </div>

        <!-- Sidebar -->
        <div class="sidebar sidebar-style-2">
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <div class="user">
                        <div class="avatar-sm float-left mr-3">
                            <img src="<?php echo base_url('uploads/logo/' . $this->apk[0]->logo); ?>" alt="..." class="avatar-img rounded-circle">
                        </div>
                        <div class="info">
                            <a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
                                <span>
                                    <?php echo $_SESSION['nama']; ?>
                                    <span class="user-level"><?php echo ucfirst($_SESSION['jabatan']); ?></span>
                                </span>
                            </a>


                        </div>
                    </div>
                    <ul class="nav nav-primary">
                        <?php
                        $jabatan = $this->session->userdata('jabatan');
                        $is_superadmin = ($jabatan == 'superadmin');
                        $is_admin = ($jabatan == 'admin');
                        $is_finance = ($jabatan == 'finance');
                        ?>

                        <!-- Dashboard - All Roles -->
                        <li class="nav-item <?= $this->uri->segment(1) == 'Dashboard' ? 'active' : ''; ?>">
                            <a href="<?php echo site_url('Dashboard'); ?>" class="collapsed" aria-expanded="false">
                                <i class="fas fa-chart-bar"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <!-- Masterdata - Superadmin Only -->
                        <?php if ($is_superadmin || $is_admin) { ?>
                            <li class="nav-item <?= $this->uri->segment(1) == 'MasterData' ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#masterdata">
                                    <i class="fas fa-layer-group"></i>
                                    <p>Masterdata</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="masterdata">
                                    <ul class="nav nav-collapse">
                                        <?php if ($is_superadmin) { ?>
                                            <li>
                                                <a href="<?php echo site_url('MasterData/pengguna'); ?>">
                                                    <span class="sub-item">User</span>
                                                </a>
                                            </li>
                                        <?php } ?>

                                        <li>
                                            <a href="<?php echo site_url('MasterData/jenis_kelas'); ?>">
                                                <span class="sub-item">Jenis Kelas</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('MasterData/pengajar'); ?>">
                                                <span class="sub-item">Pengajar</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('MasterData/level_siswa'); ?>">
                                                <span class="sub-item">Level Siswa</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        <?php } ?>

                        <!-- CRM - Superadmin & Admin -->
                        <?php if ($is_superadmin || $is_admin) { ?>
                            <li class="nav-item <?= $this->uri->segment(1) == 'Crm' ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#crm">
                                    <i class="fas fa-user"></i>
                                    <p>Crm</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="crm">
                                    <ul class="nav nav-collapse">
                                        <li>
                                            <a href="<?php echo site_url('Crm/lead'); ?>">
                                                <span class="sub-item">Lead</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Siswa - Superadmin & Admin -->
                            <li class="nav-item <?= $this->uri->segment(1) == 'Peserta' ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#peserta">
                                    <i class="fas fa-users"></i>
                                    <p>Siswa</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="peserta">
                                    <ul class="nav nav-collapse">
                                        <li>
                                            <a href="<?php echo site_url('Peserta/peserta'); ?>">
                                                <span class="sub-item">List Siswa</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Peserta/partnership'); ?>">
                                                <span class="sub-item">List Siswa Partnership</span>
                                            </a>
                                        </li>

                                    </ul>
                                </div>
                            </li>

                            <!-- Pembayaran - Superadmin & Admin -->
                            <li class="nav-item <?= $this->uri->segment(1) == 'Trans' ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#trans">
                                    <i class="fas fa-dollar-sign"></i>
                                    <p>Pembayaran</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="trans">
                                    <ul class="nav nav-collapse">
                                        <li>
                                            <a href="<?php echo site_url('Trans/tagihan'); ?>">
                                                <span class="sub-item">Tagihan</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Trans/reminder_tagihan'); ?>">
                                                <span class="sub-item">Reminder Tagihan</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Penjadwalan - Superadmin & Admin -->
                            <li class="nav-item <?= $this->uri->segment(1) == 'Penjadwalan' ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#penjadwalan">
                                    <i class="fas fa-calendar-alt"></i>
                                    <p>Penjadwalan</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="penjadwalan">
                                    <ul class="nav nav-collapse">
                                        <li>
                                            <a href="<?php echo site_url('Penjadwalan/absensi_guru'); ?>">
                                                <span class="sub-item">Absensi Guru</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Penjadwalan/laporan_absensi'); ?>">
                                                <span class="sub-item">Laporan Absensi</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Penjadwalan/jadwal_kelas'); ?>">
                                                <span class="sub-item">Jadwal Kelas</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Penjadwalan/calendar'); ?>">
                                                <span class="sub-item">Kalender Jadwal</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Penjadwalan/penempatan_guru'); ?>">
                                                <span class="sub-item">Penempatan Guru</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Penjadwalan/reschedule'); ?>">
                                                <span class="sub-item">Reschedule Kelas</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Penjadwalan/libur_nasional'); ?>">
                                                <span class="sub-item">Libur Nasional</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        <?php } ?>

                        <!-- Keuangan - Superadmin & Finance -->
                        <?php if ($is_superadmin || $is_finance) { ?>
                            <li class="nav-item <?= $this->uri->segment(1) == 'Pengeluaran' ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#keuangan">
                                    <i class="fas fa-dollar-sign"></i>
                                    <p>Keuangan</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="keuangan">
                                    <ul class="nav nav-collapse">
                                        <li>
                                            <a href="<?php echo site_url('Pengeluaran/index'); ?>">
                                                <span class="sub-item">Pengeluaran</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- HR & Payroll - Superadmin & Finance -->
                            <li class="nav-item <?= $this->uri->segment(1) == 'Hr' ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#hr">
                                    <i class="fas fa-user-tie"></i>
                                    <p>HR & Payroll</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="hr">
                                    <ul class="nav nav-collapse">
                                        <li>
                                            <a href="<?php echo site_url('Hr/karyawan'); ?>">
                                                <span class="sub-item">Data Karyawan</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Hr/payroll_guru'); ?>">
                                                <span class="sub-item">Payroll Guru</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Hr/payroll_staff'); ?>">
                                                <span class="sub-item">Payroll Staff</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Hr/pph21_komponen'); ?>">
                                                <span class="sub-item">Komponen PPh21</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Hr/laporan_payroll'); ?>">
                                                <span class="sub-item">Laporan Payroll</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        <?php } ?>
                        <li class="nav-item <?= $this->uri->segment(1) == 'Report' ? 'active' : ''; ?>">
                            <a data-toggle="collapse" href="#report">
                                <i class="fas fa-folder-open"></i>
                                <p>Report</p>
                                <span class="caret"></span>
                            </a>
                            <div class="collapse" id="report">
                                <ul class="nav nav-collapse">
                                    <!-- <li>
                                        <a href="<?php echo site_url('Report/lap_jadwal_pengajar'); ?>">
                                            <span class="sub-item">Laporan Jadwal Pengajar</span>
                                        </a>
                                    </li> -->
                                    <li>
                                        <a href="<?php echo site_url('Report/lap_inquiry_orangtua'); ?>">
                                            <span class="sub-item">Laporan Inquiry Orangtua</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo site_url('Report/lap_lead'); ?>">
                                            <span class="sub-item">Laporan Lead</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo site_url('Report/lap_murid_trial'); ?>">
                                            <span class="sub-item">Laporan Murid Trial</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo site_url('Report/lap_murid_aktif'); ?>">
                                            <span class="sub-item">Laporan Murid Aktif</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo site_url('Report/lap_murid_cuti'); ?>">
                                            <span class="sub-item">Laporan Murid Cuti</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo site_url('Report/lap_murid_resign'); ?>">
                                            <span class="sub-item">Laporan Murid Mengundurkan Diri</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo site_url('Report/lap_kelas_jadwal'); ?>">
                                            <span class="sub-item">Laporan Kelas & Jadwal</span>
                                        </a>
                                    </li>

                                    <!-- <?php if ($this->session->userdata('jabatan') == 'superadmin') { ?>
                                        <li>
                                            <a href="<?php echo site_url('Report/lap_peserta_aktif'); ?>">
                                                <span class="sub-item">Laporan Peserta Aktif</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Report/lap_pembayaran_peserta'); ?>">
                                                <span class="sub-item">Laporan Pembayaran Peserta</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Report/lap_keberlanjutan_peserta'); ?>">
                                                <span class="sub-item">Laporan Keberlanjutan Peserta</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo site_url('Report/lap_transaksi'); ?>">
                                                <span class="sub-item">Laporan Transaksi</span>
                                            </a>
                                        </li>
                                    <?php } ?> -->
                                </ul>
                            </div>
                        </li>
                        <?php if ($this->session->userdata('jabatan') == 'superadmin') { ?>

                            <li class="nav-item">
                                <a data-toggle="collapse" href="#sidebarLayouts">
                                    <i class="fas fa-cog  "></i>
                                    <p>Konfig Sistem</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="sidebarLayouts">
                                    <ul class="nav nav-collapse">
                                        <li>
                                            <a href="<?php echo site_url('MasterData/konfig'); ?>">
                                                <span class="sub-item">Pengaturan Sistem</span>
                                            </a>
                                        </li>

                                    </ul>
                                </div>
                            </li>
                        <?php } ?>

                    </ul>
                </div>
            </div>
        </div>
        <div class="main-panel">
            <div class="content">