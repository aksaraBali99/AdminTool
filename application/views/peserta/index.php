<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Pengelolaan Siswa</h4>
                    <div class="ml-auto">
                        <button class="btn btn-success btn-round btn-import-peserta" data-toggle="modal">
                            <i class="fa fa-file-excel mr-2"></i> Import Siswa
                        </button>
                        <button class="btn btn-primary btn-round btn-add-peserta" data-toggle="modal">
                            <i class="fa fa-plus mr-2"></i> Tambah Siswa
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <select id="filter_kelas" class="form-control">
                            <option value="">-- Semua Kelas --</option>
                            <?php foreach ($jenis_kelas as $jk) : ?>
                                <option value="<?= $jk->id_jenis_kelas ?>"><?= $jk->nama_kelas ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filter_aktif" class="form-control">
                            <option value="">-- Semua Status --</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Non Aktif">Non Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-info" id="btn-filter"><i class="fa fa-search"></i> Filter</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="peserta-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Anak</th>
                                <th>Umur</th>
                                <th>Level Sekolah</th>
                                <th>Nama Sekolah</th>
                                <th>Level Siswa</th>
                                <th>Status</th>
                                <th>
                                    <center>Aksi</center>
                                </th>
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
                            <thead>
                                <tr>
                                    <th>Detail Jadwal</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal tambah/edit siswa -->
        <div class="modal fade m-add-peserta" id="add-peserta" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="form-peserta">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="label_tipe">Tambah</span> Siswa</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_peserta">
                            <input type="hidden" name="tipe_form" value="add">
                            <input type="hidden" name="status" value="Registrasi Kelas">

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
                                    <select name="jk" class="form-control" required>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Tanggal Lahir</label>
                                    <input type="date" name="tgl_lahir_anak" class="form-control">
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
                            <!-- Data Kelas -->
                            <h6 class="mb-3"><strong>Data Kelas</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Jenis Kelas <span class="text-danger">*</span></label>
                                    <select name="id_jenis_kelas" class="form-control" required>
                                        <option value="">--Pilih Kelas--</option>
                                        <?php foreach ($jenis_kelas as $jk) : ?>
                                            <option value="<?= $jk->id_jenis_kelas ?>"><?= $jk->nama_kelas ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Sumber (info)</label>
                                    <select name="src" class="form-control">
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
                            </div>
                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="catatan" class="form-control" rows="2"></textarea>
                            </div>

                            <hr>
                            <!-- Jadwal -->
                            <h6 class="mb-3"><strong>Jadwal</strong></h6>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <div id="jadwal-container"></div>
                                    <button type="button" class="btn btn-sm btn-success" id="add-jadwal"><i class="fa fa-plus"></i> Tambah Jadwal</button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Siswa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal combined Invoice dengan Multiple Row Items -->
    <div class="modal fade" id="combinedInvoiceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="max-width: 95%;">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-file-invoice-dollar mr-2"></i> Kirim Tagihan Siswa</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form id="form-combined-invoice">
                    <div class="modal-body">
                        <input type="hidden" id="comb_id_peserta" name="id_peserta">
                        <input type="hidden" id="comb_biaya_kelas" value="0">
                        <input type="hidden" id="comb_biaya_regis" value="0">
                        <input type="hidden" id="comb_biaya_buku" value="0">

                        <div class="form-group mb-3">
                            <label>Nama Siswa</label>
                            <p class="form-control-plaintext font-weight-bold" id="comb_nama_siswa">-</p>
                        </div>

                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Metode Pembayaran <span class="text-danger">*</span></label>
                                    <select name="metode_pembayaran" class="form-control" required>
                                        <option value="">-- Pilih Metode Pembayaran --</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Qris">Qris</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Bulan <span class="text-danger">*</span></label>
                                    <select name="bulan" class="form-control" required>
                                        <?php 
                                        $current_month = date('m');
                                        $months = [
                                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                        ];
                                        foreach ($months as $m => $name): ?>
                                            <option value="<?= $m ?>" <?= $m == $current_month ? 'selected' : '' ?>><?= $name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Tahun <span class="text-danger">*</span></label>
                                    <select name="tahun" class="form-control" required>
                                        <?php 
                                        $current_year = date('Y');
                                        for ($y = $current_year; $y <= $current_year + 1; $y++): ?>
                                            <option value="<?= $y ?>" <?= $y == $current_year ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-row border p-2 mb-3 bg-light rounded">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label>Tipe Tagihan</label>
                                    <select name="is_prorata" id="comb_is_prorata" class="form-control">
                                        <option value="0">Normal (Full 8x)</option>
                                        <option value="1">Prorata</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="section_jml_pertemuan" style="display: none;">
                                <div class="form-group mb-0">
                                    <label>Jumlah Pertemuan</label>
                                    <input type="number" name="jml_pertemuan" id="comb_jml_pertemuan" class="form-control" value="8" min="1">
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="font-weight-bold mb-0"><i class="fa fa-list mr-2"></i>Detail Item Tagihan</h6>
                                <button type="button" class="btn btn-success btn-sm" id="btn-add-item">
                                    <i class="fa fa-plus mr-1"></i> Tambah Item
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0 table-sm" id="table-invoice-items">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 20%;">Tipe Biaya</th>
                                                <th style="width: 20%;">Keterangan</th>
                                                <th style="width: 15%;">Nilai Biaya</th>
                                                <th style="width: 15%;">Tipe Diskon</th>
                                                <th style="width: 12%;">Nilai Diskon</th>
                                                <th style="width: 12%;">Subtotal</th>
                                                <th style="width: 6%;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoice-items-container">
                                            <!-- Dynamic rows will be added here -->
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="4" class="text-right font-weight-bold">Total Diskon:</td>
                                                <td class="font-weight-bold text-danger" id="total_diskon_display">Rp 0</td>
                                                <td colspan="2"></td>
                                            </tr>
                                            <tr class="bg-primary text-white">
                                                <td colspan="4" class="text-right font-weight-bold">GRAND TOTAL:</td>
                                                <td colspan="2" class="font-weight-bold" id="grand_total_display">Rp 0</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center bg-primary text-white p-3 rounded">
                            <h5 class="mb-0 font-weight-bold">TOTAL TAGIHAN</h5>
                            <h4 class="mb-0 font-weight-bold" id="comb_total_display">Rp 0</h4>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane mr-2"></i> Kirim Tagihan Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <!-- Modal Import Siswa -->
    <div class="modal fade" id="importSiswaModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Data Siswa</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="<?= site_url('Import/import_excel_siswa') ?>" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>File Excel (.xls / .xlsx)</label>
                            <input type="file" name="fileExcel" class="form-control" accept=".xls,.xlsx" required>
                            <small class="form-text text-muted">Pastikan format kolom sesuai dengan di Excel (Nama, Nama Sekolah, Nama Orang Tua, Alamat, Email, Sumber, Status, Level Siswa).</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="text/javascript">
            $(document).ready(function() {
                const jadwalData = '<?php echo json_encode($all_jadwal); ?>';

                var table = $('#peserta-table').DataTable({
                    "ajax": {
                        url: "<?= site_url('Peserta/peserta_page') ?>",
                        type: 'POST',
                        data: function(d) {
                            d.id_kelas = $('#filter_kelas').val();
                            d.is_aktif = $('#filter_aktif').val();
                        }
                    },
                    "columnDefs": [{
                        "data": null,
                        "targets": -1,
                        "render": function(data, type, row) {
                            var wa_web = 'https://wa.me/' + row[7]; // no_hp
                            var btn = `<center>
                    <a class='btn btn-link btn-info' href='<?= site_url("Siswa/detail/") ?>${row[0]}' title="Detail Siswa"><i class='fa fa-eye'></i></a>
                    <button class='btn btn-link btn-primary btn-edit-peserta' title="Ubah Siswa" id="${row[0]}"><i class='fa fa-edit'></i></button>
                    <button class='btn btn-link btn-danger' onclick="hapusSiswa('${row[0]}')" title="Hapus Siswa"><i class='fa fa-times'></i></button>`;



                            btn += `<a class='btn btn-link btn-primary' title="Kirim Pesan Whatsapp" href="${wa_web}" target="_blank"><i class='fa fa-comment-dots'></i></a>`;
                            btn += `<button class='btn btn-link btn-primary btn-kirim-tagihan-combined' title="Kirim Tagihan" id="${row[0]}" nama="${row[1]}" biaya="${row[9]}" regis="${row[10]}" buku="${row[11]}"><i class='fa fa-file-invoice-dollar'></i></button>`;

                            btn += `</center>`;
                            return btn;
                        }
                    }],
                    "order": [
                        [0, 'desc']
                    ]
                });

                $('#btn-filter').click(function() {
                    table.ajax.reload();
                });

                function tambahJadwalRow(selectedId = '') {
                    let options = '<option value="">--Pilih Jadwal Kelas--</option>';
                    JSON.parse(jadwalData).forEach(j => {
                        const selected = j.id_jadwal_kelas == selectedId ? 'selected' : '';
                        const tipe = j.tipe_kelas == 'dewasa' ? '[Dewasa]' : '[Anak]';
                        const jenis = j.jenis_jadwal ? `(${j.jenis_jadwal})` : '(Regular)';
                        options += `<option value="${j.id_jadwal_kelas}" ${selected}>${tipe} ${j.nama_kelas} ${jenis} - ${j.nama_pengajar} | ${j.hari}, ${j.jam_mulai.substring(0,5)} - ${j.jam_selesai.substring(0,5)}</option>`;
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

                $(document).on('click', '#add-jadwal', function() {
                    tambahJadwalRow();
                });
                $(document).on('click', '.remove-jadwal', function() {
                    $(this).closest('.jadwal-row').remove();
                });

                $(document).on('click', '.btn-import-peserta', function() {
                    $('#importSiswaModal').modal('show');
                });

                $(document).on('click', '.btn-add-peserta', function() {
                    $('#label_tipe').text('Tambah');
                    $('#form-peserta')[0].reset();
                    $("input[name='tipe_form']").val('add');
                    $('#jadwal-container').html('');
                    tambahJadwalRow();
                    $('#add-peserta').modal('show');
                });

                $(document).on('click', '.btn-edit-peserta', function() {
                    $.ajax({
                        type: "POST",
                        url: '<?php echo site_url("Peserta/get_peserta"); ?>',
                        data: {
                            id_peserta: $(this).attr('id')
                        },
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
                            $("select[name='level_sekolah']").val(res.peserta.level_sekolah);
                            $("input[name='nama_sekolah']").val(res.peserta.nama_sekolah);
                            $("input[name='alamat_anak']").val(res.peserta.alamat_anak);
                            $("textarea[name='catatan']").val(res.peserta.catatan);

                            $('#jadwal-container').html('');
                            res.jadwal.forEach(j => tambahJadwalRow(j.id_jadwal_kelas));
                            $('#add-peserta').modal('show');
                        }
                    });
                });

                $(document).on('click', '.btn-set-status', function() {
                    var btn = $(this);
                    Swal.fire({
                        title: 'Yakin ' + btn.attr('fungsi') + ' Siswa ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya',
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                type: "POST",
                                url: '<?php echo site_url("Peserta/set_status"); ?>',
                                data: {
                                    id_peserta: btn.attr('id'),
                                    aktif: btn.attr('aktif')
                                },
                                dataType: "json",
                                success: function() {
                                    Swal.fire('Berhasil', 'Status siswa diupdate', 'success');
                                    table.ajax.reload();
                                }
                            });
                        }
                    });
                });

                $(document).on('click', '.btn-kirim-tagihan-combined', function() {
                    const id = $(this).attr('id');
                    const nama = $(this).attr('nama');
                    const biaya = parseFloat($(this).attr('biaya')) || 0;
                    const regis = parseFloat($(this).attr('regis')) || 0;
                    const buku = parseFloat($(this).attr('buku')) || 0;

                    $('#comb_id_peserta').val(id);
                    $('#comb_nama_siswa').text(nama);
                    $('#comb_biaya_kelas').val(biaya);
                    $('#comb_biaya_regis').val(regis);
                    $('#comb_biaya_buku').val(buku);

                    // Reset modal state - clear all items
                    $('#invoice-items-container').html('');
                    
                    // Add first row with student name pre-filled
                    addInvoiceItemRow(nama, biaya, regis, buku);

                    calculateGrandTotal();
                    $('#combinedInvoiceModal').modal('show');
                });

                // Store default prices for the current student
                let currentBiayaKelas = 0;
                let currentBiayaRegis = 0;
                let currentBiayaBuku = 0;
                let currentNamaSiswa = '';

                // Add invoice item row function
                function addInvoiceItemRow(nama = '', biayaKelas = 0, biayaRegis = 0, biayaBuku = 0) {
                    currentNamaSiswa = nama || currentNamaSiswa;
                    currentBiayaKelas = biayaKelas || currentBiayaKelas;
                    currentBiayaRegis = biayaRegis || currentBiayaRegis;
                    currentBiayaBuku = biayaBuku || currentBiayaBuku;

                    const rowIndex = $('#invoice-items-container tr').length;
                    const html = `
                    <tr class="invoice-item-row" data-index="${rowIndex}">
                        <td>
                            <select class="form-control form-control-sm item-tipe-biaya" 
                                name="items[${rowIndex}][tipe_biaya]" required>
                                <option value="">-- Pilih --</option>
                                <option value="Biaya Registrasi" data-nilai="${currentBiayaRegis}">Biaya Registrasi</option>
                                <option value="Biaya Kelas" data-nilai="${currentBiayaKelas}">Biaya Kelas</option>
                                <option value="Biaya Buku" data-nilai="${currentBiayaBuku}">Biaya Buku</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm item-keterangan" 
                                name="items[${rowIndex}][keterangan]" placeholder="Ket...">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm item-nilai-biaya" 
                                name="items[${rowIndex}][nilai_biaya]" value="0" min="0" required>
                        </td>
                        <td>
                            <select class="form-control form-control-sm item-tipe-diskon" 
                                name="items[${rowIndex}][tipe_diskon]">
                                <option value="">-- Tanpa Diskon --</option>
                                <option value="Diskon Cuti">Diskon Cuti</option>
                                <option value="Diskon Registrasi">Diskon Registrasi</option>
                                <option value="Diskon Buku">Diskon Buku</option>
                                <option value="Diskon Referal">Diskon Referal</option>
                                <option value="Diskon Kelas">Diskon Kelas</option>
                                <option value="Diskon Umum">Diskon Umum</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm item-nilai-diskon" 
                                name="items[${rowIndex}][nilai_diskon]" value="0" min="0">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm item-subtotal" 
                                name="items[${rowIndex}][subtotal]" value="0" readonly>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm btn-remove-item">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                    $('#invoice-items-container').append(html);
                }

                // Handle add item button
                $(document).on('click', '#btn-add-item', function() {
                    addInvoiceItemRow();
                });

                // Handle remove item button
                $(document).on('click', '.btn-remove-item', function() {
                    $(this).closest('.invoice-item-row').remove();
                    calculateGrandTotal();
                });

                // Handle tipe biaya change - auto fill nilai
                $(document).on('change', '.item-tipe-biaya', function() {
                    const selectedOption = $(this).find(':selected');
                    const nilai = parseInt(selectedOption.data('nilai')) || 0;
                    $(this).closest('tr').find('.item-nilai-biaya').val(nilai);
                    calculateRowSubtotal($(this).closest('tr'));
                    calculateGrandTotal();
                });

                // Handle nilai biaya change
                $(document).on('input', '.item-nilai-biaya', function() {
                    calculateRowSubtotal($(this).closest('tr'));
                    calculateGrandTotal();
                });

                // Handle nilai diskon change
                $(document).on('input', '.item-nilai-diskon', function() {
                    calculateRowSubtotal($(this).closest('tr'));
                    calculateGrandTotal();
                });

                // Handle tipe diskon change
                $(document).on('change', '.item-tipe-diskon', function() {
                    // Reset nilai diskon if no discount type selected
                    if (!$(this).val()) {
                        $(this).closest('tr').find('.item-nilai-diskon').val(0);
                    }
                    calculateRowSubtotal($(this).closest('tr'));
                    calculateGrandTotal();
                });

                // Calculate subtotal for a single row
                function calculateRowSubtotal(row) {
                    const nilaiBiaya = parseFloat(row.find('.item-nilai-biaya').val()) || 0;
                    const nilaiDiskon = parseFloat(row.find('.item-nilai-diskon').val()) || 0;
                    const subtotal = nilaiBiaya - nilaiDiskon;
                    row.find('.item-subtotal').val(subtotal >= 0 ? subtotal : 0);
                }

                // Handle Prorata change
                $(document).on('change', '#comb_is_prorata', function() {
                    const isProrata = $(this).val() == '1';
                    if (isProrata) {
                        $('#section_jml_pertemuan').fadeIn();
                    } else {
                        $('#section_jml_pertemuan').fadeOut();
                        $('#comb_jml_pertemuan').val(8);
                    }
                    recalculateProrataItems();
                });

                $(document).on('input', '#comb_jml_pertemuan', function() {
                    let val = parseInt($(this).val()) || 0;
                    if (val < 1) $(this).val(1);
                    recalculateProrataItems();
                });

                function recalculateProrataItems() {
                    const isProrata = $('#comb_is_prorata').val() == '1';
                    const jmlPertemuan = parseInt($('#comb_jml_pertemuan').val()) || 8;

                    $('.invoice-item-row').each(function() {
                        const tipeBiaya = $(this).find('.item-tipe-biaya').val();
                        if (tipeBiaya === 'Biaya Kelas') {
                            let basicPrice = currentBiayaKelas;
                            let finalPrice = isProrata ? Math.round((basicPrice / 8) * jmlPertemuan) : basicPrice;
                            $(this).find('.item-nilai-biaya').val(finalPrice);
                            calculateRowSubtotal($(this));
                        }
                    });
                    calculateGrandTotal();
                }

                // Update original addInvoiceItemRow to include prorata calculation if needed
                const originalAddInvoiceItemRow = addInvoiceItemRow;
                addInvoiceItemRow = function(nama = '', biayaKelas = 0, biayaRegis = 0, biayaBuku = 0) {
                    originalAddInvoiceItemRow(nama, biayaKelas, biayaRegis, biayaBuku);
                    recalculateProrataItems();
                };

                // Calculate grand total
                function calculateGrandTotal() {
                    let totalBiaya = 0;
                    let totalDiskon = 0;
                    
                    $('.invoice-item-row').each(function() {
                        const nilaiBiaya = parseFloat($(this).find('.item-nilai-biaya').val()) || 0;
                        const nilaiDiskon = parseFloat($(this).find('.item-nilai-diskon').val()) || 0;
                        totalBiaya += nilaiBiaya;
                        totalDiskon += nilaiDiskon;
                    });
                    
                    const grandTotal = totalBiaya - totalDiskon;
                    $('#total_diskon_display').text('Rp ' + numberFormat(totalDiskon));
                    $('#grand_total_display').text('Rp ' + numberFormat(grandTotal >= 0 ? grandTotal : 0));
                    $('#comb_total_display').text('Rp ' + numberFormat(grandTotal >= 0 ? grandTotal : 0));
                }

                $('#form-combined-invoice').on('submit', function(e) {
                    e.preventDefault();
                    
                    // Validate at least one item
                    if ($('.invoice-item-row').length === 0) {
                        Swal.fire('Peringatan', 'Tambahkan minimal satu item tagihan', 'warning');
                        return;
                    }

                    // Validate all items have tipe biaya selected
                    let valid = true;
                    $('.item-tipe-biaya').each(function() {
                        if (!$(this).val()) {
                            valid = false;
                            return false;
                        }
                    });

                    if (!valid) {
                        Swal.fire('Peringatan', 'Pilih tipe biaya untuk semua item', 'warning');
                        return;
                    }

                    // Validate all items have nilai > 0
                    let hasValue = false;
                    $('.item-nilai-biaya').each(function() {
                        if (parseFloat($(this).val()) > 0) {
                            hasValue = true;
                        }
                    });

                    if (!hasValue) {
                        Swal.fire('Peringatan', 'Nilai biaya harus lebih dari 0', 'warning');
                        return;
                    }

                    Swal.fire({
                        title: 'Kirim Tagihan?',
                        text: "Invoice akan dikirim ke WhatsApp siswa",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Kirim'
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                type: "POST",
                                url: '<?php echo site_url("Peserta/kirim_invoice"); ?>',
                                data: $(this).serialize(),
                                dataType: "json",
                                success: function(res) {
                                    if (res == 'success') {
                                        $('#combinedInvoiceModal').modal('hide');
                                        Swal.fire('Sukses', 'Invoice Berhasil dikirim', 'success');
                                        table.ajax.reload();
                                    } else {
                                        Swal.fire('Error', res.message || 'Gagal mengirim invoice', 'error');
                                    }
                                }
                            });
                        }
                    });
                });

                function numberFormat(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }

                $('#form-peserta').on('submit', function(e) {
                    e.preventDefault();
                    $.ajax({
                        type: "POST",
                        url: '<?php echo site_url("Peserta/add_peserta"); ?>',
                        data: $(this).serialize(),
                        dataType: "json",
                        success: function() {
                            Swal.fire("Sukses", "Data berhasil disimpan", "success");
                            $('#add-peserta').modal('hide');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("Error", "Gagal menghubungkan server", "error");
                        }
                    });
                });
            });

            function hapusSiswa(id) {
                Swal.fire({
                    title: 'Yakin hapus siswa ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya',
                }).then((result) => {
                    if (result.value) {
                        $.post('<?php echo site_url("Peserta/hapus_peserta"); ?>', {
                            id_peserta: id
                        }, function() {
                            Swal.fire('Berhasil', 'Siswa dihapus', 'success');
                            $('#peserta-table').DataTable().ajax.reload();
                        });
                    }
                });
            }
        </script>