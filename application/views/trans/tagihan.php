<div class="page-inner">
    <div class="page-header">
        <h4 class="page-title">Daftar Tagihan Siswa</h4>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Tagihan Siswa</h5>
            <div class="row">
                <div class="col-md-3 mt-2">
                    <select id="filterTahun" class="form-control">
                        <?php
                        $tahun_sekarang = date('Y');
                        for ($t = $tahun_sekarang - 3; $t <= $tahun_sekarang + 2; $t++) {
                            $selected = ($t == $tahun_sekarang) ? 'selected' : '';
                            echo "<option value='$t' $selected>$t</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <select id="filterBulan" class="form-control">
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $selected = ($i == date('n')) ? 'selected' : '';
                            echo "<option value='$i' $selected>" . date('F', mktime(0, 0, 0, $i, 10)) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <select id="filterStatus" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Late">Late</option>
                        <option value="Refund">Refund</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <button id="filterBtn" class="btn btn-primary btn-sm">Terapkan Filter</button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="tagihanTable" class="table table-bordered table-striped" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">Nama</th>
                        <th style="width: 10%;">No HP</th>
                        <th style="width: 8%;">Bulan</th>
                        <th style="width: 6%;">Tahun</th>
                        <th style="width: 10%;">Jumlah</th>
                        <th style="width: 10%;">Prorata</th>
                        <th style="width: 10%;">Tipe</th>
                        <th style="width: 8%;">Status</th>
                        <th style="width: 12%;">Tgl Bayar</th>
                        <th style="width: 10%;">Fungsi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Modal untuk Konfirmasi Pembayaran -->
        <div class="modal fade" id="bayarModal" tabindex="-1" role="dialog" aria-labelledby="bayarModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bayarModalLabel">Konfirmasi Pembayaran</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="form_update_pembayaran">
                            <p>Apakah Anda yakin ingin update <strong>Lunas</strong> tagihan untuk Peserta <strong id="nama_siswa"></strong>?</p>
                            <p><strong>Jumlah:</strong> <span id="jumlah_tagihan"></span></p>
                            <input type="hidden" name="id_tagihan">
                            <button type="submit" class="btn btn-primary" id="btnBayar">Bayar</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="ditagihModal" tabindex="-1" role="dialog" aria-labelledby="ditagihModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bayarModalLabel">Konfirmasi Dalam Penagihan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="form_update_ditagih">
                            <p>Apakah Anda yakin ingin ubah tagihan ini menjadi <strong>Sedang ditagih</strong> untuk Peserta <strong id="nama_siswa_ditagih"></strong>?</p>
                            <input type="hidden" name="id_tagihan_tagih">
                            <button type="submit" class="btn btn-primary" id="btnDitagih">Ya</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(document).ready(function() {
                var table = $('#tagihanTable').DataTable({
                    scrollX: true,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '<?= site_url('Trans/tagihan_page') ?>',
                        type: 'POST',
                        data: function(d) {
                            d.bulan = $('#filterBulan').val();
                            d.tahun = $('#filterTahun').val();
                            d.status = $('#filterStatus').val();
                        }
                    },
                    order: [[0, 'desc']], // Default: Urutkan berdasarkan nomor urut (invoice terbaru) descending
                    columnDefs: [
                        {
                            targets: 0, // Kolom No
                            orderable: false // Tidak bisa diurutkan karena dibuat manual di backend
                        },
                        {
                            targets: 6, // Kolom Prorata
                            orderable: false // Tidak perlu diurutkan
                        },
                        {
                            targets: -1, // Kolom Fungsi (terakhir)
                            orderable: false
                        }
                    ]
                });

                $('#filterBulan, #filterTahun, #filterStatus').on('change', function() {
                    table.draw();
                });

                $(document).on('click', '.btn-bayar', function() {
                    var id = $(this).attr('id');
                    var nama = $(this).attr('nama');
                    var nominal = $(this).attr('nominal');

                    $("input[name='id_tagihan']").val(id);
                    $("#nama_siswa").text(nama);
                    $("#jumlah_tagihan").text(nominal);
                    $('#bayarModal').modal('show');
                });

                $(document).on('click', '.btn-late', function() {
                    var id = $(this).attr('id');
                    var nama = $(this).attr('nama');

                    Swal.fire({
                        title: 'Konfirmasi',
                        text: 'Ubah status tagihan ' + nama + ' menjadi Late?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.value) {
                            $.post('<?= site_url("Trans/late") ?>', {
                                id_tagihan: id
                            }, function(res) {
                                Swal.fire('Berhasil!', 'Status tagihan diubah ke Late', 'success');
                                table.ajax.reload(null, false);
                            }, 'json');
                        }
                    });
                });

                $(document).on('click', '.btn-refund', function() {
                    var id = $(this).attr('id');
                    var nama = $(this).attr('nama');

                    Swal.fire({
                        title: 'Konfirmasi Refund',
                        text: 'Ubah status tagihan ' + nama + ' menjadi Refund?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Refund',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.value) {
                            $.post('<?= site_url("Trans/refund") ?>', {
                                id_tagihan: id
                            }, function(res) {
                                Swal.fire('Berhasil!', 'Status tagihan diubah ke Refund', 'success');
                                table.ajax.reload(null, false);
                            }, 'json');
                        }
                    });
                });

                $(document).on('submit', '#form_update_pembayaran', function(e) {
                    e.preventDefault();

                    $.ajax({
                        type: "POST",
                        url: '<?php echo site_url("Trans/bayar"); ?>',
                        data: {
                            id_tagihan: $("input[name='id_tagihan']").val()
                        },
                        dataType: "json",
                        success: function(res) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-right',
                                showConfirmButton: false,
                                timer: 3000
                            });
                            Toast.fire({
                                type: 'success',
                                title: 'Pembayaran Berhasil - Status: Paid'
                            });

                            $('#bayarModal').modal('hide');
                            table.ajax.reload(null, false);
                        }
                    });
                });

                // Keep ditagih form for backward compatibility
                $(document).on('submit', '#form_update_ditagih', function(e) {
                    e.preventDefault();

                    $.ajax({
                        type: "POST",
                        url: '<?php echo site_url("Trans/late"); ?>',
                        data: {
                            id_tagihan: $("input[name='id_tagihan_tagih']").val()
                        },
                        dataType: "json",
                        success: function(res) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-right',
                                showConfirmButton: false,
                                timer: 3000
                            });
                            Toast.fire({
                                type: 'success',
                                title: 'Status diubah ke Late'
                            });

                            $('#ditagihModal').modal('hide');
                            table.ajax.reload(null, false);
                        }
                    });
                });

                $(document).on('click', '.btn-hapus-tagihan', function() {
                    var id = $(this).attr('id');
                    var nama = $(this).attr('nama');

                    Swal.fire({
                        title: 'Hapus Tagihan?',
                        text: 'Apakah Anda yakin ingin menghapus tagihan ' + nama + '? Data yang dihapus tidak dapat dikembalikan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.value) {
                            $.post('<?= site_url("Trans/hapus_tagihan") ?>', {
                                id_tagihan: id
                            }, function(res) {
                                if (res.status === 'success') {
                                    Swal.fire('Berhasil!', res.message, 'success');
                                    table.ajax.reload(null, false);
                                } else {
                                    Swal.fire('Gagal!', res.message, 'error');
                                }
                            }, 'json');
                        }
                    });
                });
            });
        </script>