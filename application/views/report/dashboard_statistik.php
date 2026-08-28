<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Dashboard Statistik Murid</h4>
                    <div class="ml-auto">
                        <button class="btn btn-primary" id="btn-refresh">
                            <i class="fa fa-refresh mr-1"></i> Refresh Data
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card card-stats card-round" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                            <div class="card-body" style="color: white;">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fa fa-phone fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category" style="color: rgba(255,255,255,0.8);">Inquiry Orangtua</p>
                                            <h4 class="card-title" id="stat-inquiry">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats card-round" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                            <div class="card-body" style="color: white;">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fa fa-clipboard fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category" style="color: rgba(255,255,255,0.8);">Total Trial</p>
                                            <h4 class="card-title" id="stat-trial">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats card-round" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                            <div class="card-body" style="color: white;">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fa fa-user-check fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category" style="color: rgba(255,255,255,0.8);">Murid Aktif</p>
                                            <h4 class="card-title" id="stat-aktif">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats card-round" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                            <div class="card-body" style="color: white;">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fa fa-user-graduate fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category" style="color: rgba(255,255,255,0.8);">Murid Trial</p>
                                            <h4 class="card-title" id="stat-murid-trial">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card card-stats card-round" style="background: linear-gradient(135deg, #f1c40f, #f39c12);">
                            <div class="card-body" style="color: white;">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fa fa-pause-circle fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category" style="color: rgba(255,255,255,0.8);">Murid Cuti</p>
                                            <h4 class="card-title" id="stat-cuti">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats card-round" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                            <div class="card-body" style="color: white;">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fa fa-user-times fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category" style="color: rgba(255,255,255,0.8);">Mengundurkan Diri</p>
                                            <h4 class="card-title" id="stat-resign">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats card-round" style="background: linear-gradient(135deg, #1abc9c, #16a085);">
                            <div class="card-body" style="color: white;">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fa fa-chalkboard fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category" style="color: rgba(255,255,255,0.8);">Total Kelas</p>
                                            <h4 class="card-title" id="stat-kelas">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats card-round" style="background: linear-gradient(135deg, #34495e, #2c3e50);">
                            <div class="card-body" style="color: white;">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fa fa-calendar-alt fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category" style="color: rgba(255,255,255,0.8);">Jadwal Aktif</p>
                                            <h4 class="card-title" id="stat-jadwal">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <!-- Detail Tables -->
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="pills-inquiry-tab" data-toggle="pill" href="#pills-inquiry" role="tab">
                            <i class="fa fa-phone"></i> Inquiry Orangtua
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-kelas-tab" data-toggle="pill" href="#pills-kelas" role="tab">
                            <i class="fa fa-chalkboard"></i> Kelas & Jadwal
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content" id="pills-tabContent">
                    <!-- Tab Inquiry -->
                    <div class="tab-pane fade show active" id="pills-inquiry" role="tabpanel">
                        <div class="table-responsive">
                            <table id="table-inquiry" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Orangtua</th>
                                        <th>No HP</th>
                                        <th>Nama Anak</th>
                                        <th>Tanggal Hubungi</th>
                                        <th>Sumber</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab Kelas & Jadwal -->
                    <div class="tab-pane fade" id="pills-kelas" role="tabpanel">
                        <div class="table-responsive">
                            <table id="table-kelas" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kelas</th>
                                        <th>Jumlah Murid</th>
                                        <th>Total Jadwal</th>
                                        <th>Biaya</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function loadStats() {
        $.get('<?= site_url("Report/get_statistik_murid") ?>', function(res) {
            var data = JSON.parse(res);
            $('#stat-inquiry').text(data.inquiry);
            $('#stat-trial').text(data.trial);
            $('#stat-murid-trial').text(data.murid_trial);
            $('#stat-aktif').text(data.aktif);
            $('#stat-cuti').text(data.cuti);
            $('#stat-resign').text(data.resign);
            $('#stat-kelas').text(data.total_kelas);
            $('#stat-jadwal').text(data.total_jadwal);
        });
    }
    
    var tableInquiry = $('#table-inquiry').DataTable({
        "ajax": {
            url: '<?= site_url("Report/get_inquiry_orangtua") ?>',
            type: 'GET'
        },
        "order": [[4, 'desc']]
    });
    
    var tableKelas = $('#table-kelas').DataTable({
        "ajax": {
            url: '<?= site_url("Report/get_kelas_jadwal") ?>',
            type: 'GET'
        },
        "order": [[2, 'desc']]
    });
    
    loadStats();
    
    $('#btn-refresh').click(function() {
        loadStats();
        tableInquiry.ajax.reload();
        tableKelas.ajax.reload();
    });
});
</script>
