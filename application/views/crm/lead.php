<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">CRM - Data Lead</h4>
                    <button class="btn btn-primary btn-round ml-auto btn-add-peserta" data-toggle="modal">
                        <i class="fa fa-plus mr-2"></i> Tambah Lead
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Status -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <select id="filter_status" class="form-control">
                            <option value="">-- Semua Status --</option>
                            <option value="Info Harga">Info Harga</option>
                            <option value="Jadwal Trial">Jadwal Trial</option>
                            <option value="Placement Test">Placement Test</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filter_sumber" class="form-control">
                            <option value="">-- Semua Sumber --</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Google Maps">Google Maps</option>
                            <option value="TikTok">TikTok</option>
                            <option value="Dari Teman">Dari Teman</option>
                            <option value="Dekat Rumah">Dekat Rumah</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-info" id="btn-filter"><i class="fa fa-search"></i> Filter</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="lead-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Ortu</th>
                                <th>No HP</th>
                                <th>Nama Anak</th>
                                <th>Tgl Lahir</th>
                                <th>Email</th> 
                                <th>Sumber</th>
                                <th>Jenis Kelas</th>
                                <th>Jadwal</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th><center>Aksi</center></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Detail Jadwal -->
        <div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Jadwal</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <table id="jadwalDetailTable" class="table table-bordered">
                            <thead><tr><th>Detail Jadwal</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal History Lead -->
        <div class="modal fade" id="historyModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title"><i class="fa fa-history mr-2"></i>Riwayat Lead - <span id="history-nama-lead"></span></h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="historyTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="status-tab" data-toggle="tab" href="#status-history" role="tab">
                                    <i class="fa fa-exchange-alt"></i> Riwayat Status
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="kontak-tab" data-toggle="tab" href="#kontak-history" role="tab">
                                    <i class="fa fa-phone"></i> Riwayat Dihubungi
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content mt-3" id="historyTabContent">
                            <!-- Status History Tab -->
                            <div class="tab-pane fade show active" id="status-history" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm" id="status-history-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Status Lama</th>
                                                <th>Status Baru</th>
                                                <th>Diubah Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <p id="no-status-history" class="text-muted text-center" style="display:none;">Belum ada riwayat perubahan status</p>
                            </div>
                            <!-- Kontak History Tab -->
                            <div class="tab-pane fade" id="kontak-history" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm" id="kontak-history-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Tanggal Update</th>
                                                <th>Tgl Dihubungi</th>
                                                <th>Catatan</th>
                                                <th>Diubah Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <p id="no-kontak-history" class="text-muted text-center" style="display:none;">Belum ada riwayat kontak</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal tambah/edit lead -->
        <div class="modal fade m-add-peserta" id="add-peserta" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="form-peserta">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Lead</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_peserta">
                            <input type="hidden" name="tipe_form" value="add">

                            <!-- Data Orang Tua -->
                            <h6 class="mb-3"><strong>Data Orang Tua</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Nama Orang Tua <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_ortu" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>No HP <span class="text-danger">*</span></label>
                                    <input type="text" name="no_hp" class="form-control" placeholder="Format : 628xxxx" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Alamat Orang Tua</label>
                                    <input type="text" name="alamat_ortu" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>

                            <hr>
                            <!-- Data Anak -->
                            <h6 class="mb-3"><strong>Data Anak</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Nama Anak <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_anak" class="form-control" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Jenis Kelamin</label>
                                    <select name="jk" class="form-control">
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Tanggal Lahir</label>
                                    <input type="date" name="tgl_lahir_anak" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Level Sekolah</label>
                                    <select name="level_sekolah" class="form-control">
                                        <option value="">-- Pilih Level Sekolah --</option>
                                        <option value="TK">TK</option>
                                        <option value="SD">SD</option>
                                        <option value="SMP">SMP</option>
                                        <option value="SMA">SMA</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Nama Sekolah</label>
                                    <input type="text" name="nama_sekolah" class="form-control">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label>Alamat Anak</label>
                                    <input type="text" name="alamat_anak" class="form-control" placeholder="Alamat tempat tinggal anak">
                                </div>
                            </div>

                            <hr>
                            <!-- Status Lead -->
                            <h6 class="mb-3"><strong>Status Lead</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Jenis Kelas <span class="text-danger">*</span></label>
                                    <select name="id_jenis_kelas" class="form-control" required>
                                        <option value="">--Pilih Kelas--</option>
                                        <?php foreach ($jenis_kelas as $jk) : ?>
                                            <option value="<?= $jk->id_jenis_kelas ?>"><?= $jk->nama_kelas ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Sumber</label>
                                    <select name="src" class="form-control" id="sumber-lead">
                                        <option value="">--Pilih Sumber--</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="Dekat Rumah">Dekat Rumah</option>
                                        <option value="Google Maps">Google Maps</option>
                                        <option value="Google">Google</option>
                                        <option value="Dari Teman">Dari Teman</option>
                                        <option value="Dari Saudara">Dari Saudara</option>
                                        <option value="TikTok">TikTok</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Status Lead <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control" required>
                                        <option value="Info Harga">Info Harga</option>
                                        <option value="Jadwal Trial">Jadwal Trial</option>
                                        <option value="Placement Test">Placement Test</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Jenis Siswa <span class="text-danger">*</span></label>
                                    <select name="jenis_siswa" class="form-control" required>
                                        <option value="regular">Regular</option>
                                        <option value="partnership">Partnership</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row" id="referral-row" style="display:none;">
                                <div class="form-group col-md-6" style="position:relative;">
                                    <label>Nama Pemberi Referral <span class="text-muted">(Cari dari data siswa)</span></label>
                                    <input type="text" name="referral_name" id="referral-search" class="form-control" placeholder="Ketik nama siswa..." autocomplete="off">
                                    <input type="hidden" name="id_referral" id="id-referral">
                                    <div id="referral-results" style="display:none; position:absolute; top:100%; left:15px; right:15px; z-index:1050; background:#fff; border:1px solid #ddd; border-radius:4px; box-shadow:0 4px 8px rgba(0,0,0,0.15); max-height:200px; overflow-y:auto;"></div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Tgl Terakhir Dihubungi</label>
                                    <input type="date" name="tgl_terakhir_dihubungi" class="form-control">
                                </div>
                                <div class="form-group col-md-8">
                                    <label>Catatan</label>
                                    <textarea name="catatan" class="form-control" rows="1"></textarea>
                                </div>
                            </div>
                            
                            <hr>
                            <!-- Jadwal Trial/Placement -->
                            <h6 class="mb-2"><strong>Jadwal Trial/Placement</strong></h6>
                            <div class="mb-3">
                                <span class="badge badge-info" id="display-usia" style="font-size: 14px; padding: 8px 15px;"><i class="fa fa-user"></i> Usia: <span id="usia-value">-</span></span>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <div id="jadwal-container"></div>
                                    <button type="button" class="btn btn-sm btn-success" id="add-jadwal"><i class="fa fa-plus"></i> Tambah Jadwal</button>
                                </div>
                            </div>
                            
                            <hr> 
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Lead</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    const jadwalData = '<?php echo json_encode($all_jadwal); ?>';
    
    var table = $('#lead-table').DataTable({
        "ajax": {
            url: "<?= site_url('Crm/lead_page') ?>",
            type: 'POST',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.sumber = $('#filter_sumber').val();
            }
        },
        "columnDefs": [{
            "data": null,
            "targets": -1,
            "render": function(data, type, row) {
                var wa_web = 'https://wa.me/' + row[2];
                var btn = `<center> 
                    <button class='btn btn-link btn-primary btn-edit-peserta' title="Edit" id="${row[0]}"><i class='fa fa-edit'></i></button>
                    <button class='btn btn-link btn-danger' onclick="hapusLead('${row[0]}')" title="Hapus"><i class='fa fa-times'></i></button>
                    <a class='btn btn-link btn-success' href="${wa_web}" target="_blank" title="WhatsApp"><i class='fa fa-comment-dots'></i></a>
                    <button class='btn btn-link btn-warning btn-convert' data-id="${row[0]}" title="Konversi ke Siswa"><i class='fa fa-user-graduate'></i></button>
                    <button class='btn btn-link btn-info btn-history' data-id="${row[0]}" data-nama="${row[3]}" title="Riwayat"><i class='fa fa-history'></i></button>
                </center>`;
                return btn;
            }
        }],
        "order": [[0, 'desc']]
    });

    $('#btn-filter').click(function() { table.ajax.reload(); });

    function tambahJadwalRow(selectedId = '') {
        let options = '<option value="">--Pilih Jadwal Trial/Placement--</option>';
        JSON.parse(jadwalData).forEach(j => {
            const selected = j.id_jadwal_kelas == selectedId ? 'selected' : '';
            const tipe = j.tipe_kelas == 'dewasa' ? '[Dewasa]' : '[Anak]';
            const jenis = j.jenis_jadwal || 'Trial';
            options += `<option value="${j.id_jadwal_kelas}" ${selected}>${tipe} (${jenis}) ${j.nama_kelas} - ${j.nama_pengajar} | ${j.hari}, ${j.jam_mulai.substring(0,5)} - ${j.jam_selesai.substring(0,5)}</option>`;
        });

        const html = `
        <div class="form-row mb-2 jadwal-row">
            <div class="col-md-9">
                <select name="id_jadwal_kelas[]" class="form-control">${options}</select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger btn-sm remove-jadwal"><i class="fa fa-trash"></i></button>
            </div>
        </div>`;
        $('#jadwal-container').append(html);
    }

    function lihatDetail(data) {
        var html = '';
        var result_data = data.split('@');
        result_data.forEach(function(r) {
            html += '<tr><td>' + r + '</td></tr>';
        });
        $('#jadwalDetailTable tbody').html(html);
        $('#detailModal').modal('show');
    }
    
    // History button click handler
    $(document).on('click', '.btn-history', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        
        $('#history-nama-lead').text(nama);
        $('#status-history-table tbody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
        $('#kontak-history-table tbody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
        
        $.ajax({
            type: "POST",
            url: '<?= site_url("Crm/get_lead_history") ?>',
            data: { id_peserta: id },
            dataType: "json",
            success: function(res) {
                // Populate status history
                var statusHtml = '';
                if (res.status_history && res.status_history.length > 0) {
                    res.status_history.forEach(function(h) {
                        var tgl = new Date(h.tgl_update).toLocaleString('id-ID');
                        statusHtml += '<tr>';
                        statusHtml += '<td>' + tgl + '</td>';
                        statusHtml += '<td>' + (h.status_lama || '<em>- baru -</em>') + '</td>';
                        statusHtml += '<td><span class="badge badge-warning">' + h.status_baru + '</span></td>';
                        statusHtml += '<td>' + (h.updated_by_name || '-') + '</td>';
                        statusHtml += '</tr>';
                    });
                    $('#status-history-table tbody').html(statusHtml);
                    $('#status-history-table').show();
                    $('#no-status-history').hide();
                } else {
                    $('#status-history-table tbody').html('');
                    $('#status-history-table').hide();
                    $('#no-status-history').show();
                }
                
                // Populate kontak history
                var kontakHtml = '';
                if (res.kontak_history && res.kontak_history.length > 0) {
                    res.kontak_history.forEach(function(h) {
                        var tglUpdate = new Date(h.tgl_update).toLocaleString('id-ID');
                        var tglKontak = h.tgl_kontak ? new Date(h.tgl_kontak).toLocaleDateString('id-ID') : '-';
                        kontakHtml += '<tr>';
                        kontakHtml += '<td>' + tglUpdate + '</td>';
                        kontakHtml += '<td>' + tglKontak + '</td>';
                        kontakHtml += '<td>' + (h.catatan || '-') + '</td>';
                        kontakHtml += '<td>' + (h.updated_by_name || '-') + '</td>';
                        kontakHtml += '</tr>';
                    });
                    $('#kontak-history-table tbody').html(kontakHtml);
                    $('#kontak-history-table').show();
                    $('#no-kontak-history').hide();
                } else {
                    $('#kontak-history-table tbody').html('');
                    $('#kontak-history-table').hide();
                    $('#no-kontak-history').show();
                }
            }
        });
        
        $('#historyModal').modal('show');
    });

    $(document).on('click', '.btn-jadwal-detail', function() {
        lihatDetail($(this).data('jadwal'));
    });

    $(document).on('click', '#add-jadwal', function() { tambahJadwalRow(); });
    $(document).on('click', '.remove-jadwal', function() { $(this).closest('.jadwal-row').remove(); });
    
    // Kalkulasi usia otomatis dari tanggal lahir
    function hitungUsia() {
        var tglLahir = $("input[name='tgl_lahir_anak']").val();
        if (tglLahir) {
            var birthDate = new Date(tglLahir);
            var today = new Date();
            var age = today.getFullYear() - birthDate.getFullYear();
            var m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            $('#usia-value').text(age + ' tahun');
        } else {
            $('#usia-value').text('-');
        }
    }
    
    // Trigger saat tanggal lahir berubah
    $(document).on('change input', "input[name='tgl_lahir_anak']", function() {
        hitungUsia();
    });

    $(document).on('click', '.btn-add-peserta', function() {
        $('#label_tipe').text('Tambah');
        $('#form-peserta')[0].reset();
        $("input[name='tipe_form']").val('add');
        $('#jadwal-container').html('');
        $('#usia-value').text('-');
        $('#add-peserta').modal('show');
    });

    $(document).on('click', '.btn-edit-peserta', function() {
        $.ajax({
            type: "POST",
            url: '<?php echo site_url("Peserta/get_peserta"); ?>',
            data: { id_peserta: $(this).attr('id') },
            dataType: "json",
            success: function(res) {
                $('#label_tipe').text('Ubah');
                $("input[name='tipe_form']").val('edit');
                $("input[name='id_peserta']").val(res.peserta.id_peserta);
                $("input[name='nama_ortu']").val(res.peserta.nama_ortu);
                $("input[name='no_hp']").val(res.peserta.no_hp);
                $("input[name='alamat_ortu']").val(res.peserta.alamat_ortu);
                $("input[name='email']").val(res.peserta.email);
                $("input[name='nama_anak']").val(res.peserta.nama_anak);
                $("select[name='jk']").val(res.peserta.jk);
                $("input[name='tgl_lahir_anak']").val(res.peserta.tgl_lahir_anak);
                $("select[name='id_jenis_kelas']").val(res.peserta.id_jenis_kelas);
                $("select[name='src']").val(res.peserta.src);
                $("select[name='status']").val(res.peserta.status);
                $("input[name='tgl_terakhir_dihubungi']").val(res.peserta.tgl_terakhir_dihubungi);
                $("select[name='level_sekolah']").val(res.peserta.level_sekolah);
                $("input[name='nama_sekolah']").val(res.peserta.nama_sekolah);
                $("input[name='alamat_anak']").val(res.peserta.alamat_anak);
                $("textarea[name='catatan']").val(res.peserta.catatan);
                $("select[name='jenis_siswa']").val(res.peserta.jenis_siswa || 'regular');

                $('#jadwal-container').html('');
                res.jadwal.forEach(j => tambahJadwalRow(j.id_jadwal_kelas));
                
                // Set referral if exists
                if (res.peserta.referral_name) {
                    $('#referral-search').val(res.peserta.referral_name);
                    $('#id-referral').val(res.peserta.id_referral);
                }
                toggleReferralField();
                hitungUsia();
                
                $('#add-peserta').modal('show');
            }
        });
    });
    
    // Toggle referral field based on sumber selection
    function toggleReferralField() {
        var sumber = $('#sumber-lead').val();
        if (sumber == 'Dari Teman' || sumber == 'Dari Saudara') {
            $('#referral-row').show();
        } else {
            $('#referral-row').hide();
            $('#referral-search').val('');
            $('#id-referral').val('');
        }
    }
    
    $('#sumber-lead').change(function() {
        toggleReferralField();
    });
    
    // Autocomplete for referral search
    var searchTimeout;
    $('#referral-search').on('input', function() {
        var query = $(this).val();
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            $('#referral-results').html('').hide();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.post('<?= site_url("Crm/search_siswa_referral") ?>', {query: query}, function(res) {
                var data = JSON.parse(res);
                var html = '';
                
                if (data.length > 0) {
                    data.forEach(function(siswa) {
                        html += `<div class="referral-item" data-id="${siswa.id_peserta}" data-nama="${siswa.nama_anak}" style="padding:10px 15px; cursor:pointer; border-bottom:1px solid #eee;">
                            <div style="font-weight:600; color:#333;">${siswa.nama_anak}</div>
                            <div style="font-size:12px; color:#888;">Ortu: ${siswa.nama_ortu} | ${siswa.no_hp}</div>
                        </div>`;
                    });
                } else {
                    html = '<div style="padding:10px 15px; color:#888; text-align:center;">Tidak ditemukan</div>';
                }
                
                $('#referral-results').html(html).show();
            });
        }, 300);
    });
    
    $(document).on('click', '.referral-item', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#referral-search').val(nama);
        $('#id-referral').val(id);
        $('#referral-results').html('').hide();
    });
    
    // Hover effect for referral items
    $(document).on('mouseenter', '.referral-item', function() {
        $(this).css('background-color', '#f0f7ff');
    }).on('mouseleave', '.referral-item', function() {
        $(this).css('background-color', '#fff');
    });
    
    // Hide results when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('#referral-search, #referral-results').length) {
            $('#referral-results').hide();
        }
    });

    // Convert Lead to Siswa
    $(document).on('click', '.btn-convert', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Konversi Lead ke Siswa?',
            text: 'Status akan diubah ke Registrasi Kelas',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Konversi'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Crm/convert_lead") ?>', {id_peserta: id}, function(res) {
                    if (res.includes('sukses')) {
                        Swal.fire('Berhasil', 'Lead berhasil dikonversi ke Siswa', 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    });

    $('#form-peserta').on('submit', function(e) {
        e.preventDefault();
        var tipeForm = $("input[name='tipe_form']").val();
        var url = tipeForm == 'add' ? '<?= site_url("Crm/add_lead") ?>' : '<?= site_url("Crm/update_lead") ?>';
        
        $.ajax({
            type: "POST",
            url: url,
            data: $(this).serialize(),
            dataType: "json",
            success: function() {
                Swal.fire("Sukses", "Lead berhasil disimpan", "success");
                $('#add-peserta').modal('hide');
                table.ajax.reload();
            },
            error: function() { Swal.fire("Error", "Gagal menyimpan data", "error"); }
        });
    });
});

function hapusLead(id) {
    Swal.fire({
        title: 'Yakin hapus lead ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya',
    }).then((result) => {
        if (result.value) {
            $.post('<?php echo site_url("Peserta/hapus_peserta"); ?>', {id_peserta: id}, function() {
                Swal.fire('Berhasil', 'Lead dihapus', 'success');
                $('#lead-table').DataTable().ajax.reload();
            });
        }
    });
}
</script>

