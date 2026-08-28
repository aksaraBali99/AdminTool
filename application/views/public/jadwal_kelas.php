<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - <?= $apk[0]->nama_apk ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #27ae60;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .logo-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
        .logo-title img {
            height: 60px;
        }
        
        .logo-title h1 {
            color: var(--primary-color);
            margin: 0;
            font-weight: 700;
        }
        
        .logo-title p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: white;
        }
        
        .stat-card .icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-card .icon.green { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stat-card .icon.orange { background: linear-gradient(135deg, #e74c3c, #e67e22); }
        
        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #7f8c8d;
            margin: 0;
            font-size: 14px;
        }
        
        .table-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        
        .table-card h4 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-card h4 i {
            color: var(--secondary-color);
        }
        
        .badge-secondary {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .badge-info {
            background: linear-gradient(135deg, #1abc9c, #16a085);
        }
        
        .badge-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
        
        .footer {
            background: rgba(0, 0, 0, 0.2);
            color: white;
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        .updated-time {
            text-align: right;
            color: #7f8c8d;
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .stat-card { margin-bottom: 20px; }
            .logo-title { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-section">
        <div class="container">
            <div class="logo-title">
                <?php if (!empty($apk[0]->logo)): ?>
                <img src="<?= base_url('uploads/logo/' . $apk[0]->logo) ?>" alt="Logo">
                <?php endif; ?>
                <div>
                    <h1><?= $apk[0]->nama_apk ?></h1> 
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Stats Cards --> 

        <!-- Table -->
        <div class="table-card">
            <div class="updated-time">
                <i class="fas fa-sync-alt"></i> Terakhir diupdate: <span id="last-update"></span>
            </div>
            <h4><i class="fas fa-table"></i> Jadwal Kelas</h4>
            <div class="table-responsive">
                <table id="jadwal-table" class="table table-striped table-hover" style="width:100%">
                    <thead class="thead-dark">
                        <tr>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Kelas</th>
                            <th>Pengajar</th>
                            <th>Ruangan</th>
                            <th>Tipe / Jenis</th>
                            <th>Peserta</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detail Peserta -->
    <div class="modal fade" id="modal-detail" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                    <h5 class="modal-title"><i class="fas fa-users mr-2"></i>Peserta - <span id="modal-nama-kelas"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="table-peserta">
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

    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= $apk[0]->nama_apk ?>. All rights reserved.</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() {
        // Load summary stats
        $.get('<?= site_url("Pub/get_summary") ?>', function(res) {
            var data = JSON.parse(res);
            $('#stat-kelas').text(data.total_kelas);
            $('#stat-siswa').text(data.total_siswa);
            $('#stat-guru').text(data.total_guru);
        });

        // Update time
        var now = new Date();
        $('#last-update').text(now.toLocaleString('id-ID'));

        // DataTable
        $('#jadwal-table').DataTable({
            "ajax": {
                url: '<?= site_url("Pub/get_jadwal_data") ?>',
                type: 'GET'
            },
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "info": "Menampilkan _START_ - _END_ dari _TOTAL_ jadwal",
                "infoEmpty": "Tidak ada data",
                "zeroRecords": "Data tidak ditemukan",
                "paginate": {
                    "first": "Awal",
                    "last": "Akhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            },
            "order": [[0, 'asc'], [1, 'asc']],
            "pageLength": 25
        });

        // Detail button click
        $(document).on('click', '.btn-detail', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            
            $('#modal-nama-kelas').text(nama);
            
            $.get('<?= site_url("Pub/get_peserta_jadwal") ?>/' + id, function(res) {
                var data = JSON.parse(res);
                var tbody = $('#table-peserta tbody');
                tbody.html('');
                
                if (data.length == 0) {
                    tbody.html('<tr><td colspan="5" class="text-center text-muted">Belum ada peserta terdaftar</td></tr>');
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
                            <td>${row.no_hp || '-'}</td>
                            <td>${status_badge}</td>
                        </tr>`);
                    });
                }
                
                $('#modal-detail').modal('show');
            });
        });

        // Auto refresh every 5 minutes
        setInterval(function() {
            $('#jadwal-table').DataTable().ajax.reload();
            $('#last-update').text(new Date().toLocaleString('id-ID'));
        }, 300000);
    });
    </script>
</body>
</html>
