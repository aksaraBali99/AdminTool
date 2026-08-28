<!-- <style>
    /* Sticky kolom pertama (kolom jam) */
    #jadwalKosongTable th:first-child,
    #jadwalKosongTable td:first-child {
        position: sticky;
        left: 0;
        background-color: #fff;
        z-index: 1;
        min-width: 120px;
    }

    #jadwalKosongTable thead th {
        z-index: 2;
    }
</style> -->
<div class="page-inner">
    <div class="page-header">
        <h4 class="page-title">Laporan Jadwal Kosong Pengajar</h4>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="form-inline">
                <select id="filterPengajar" class="form-control mr-2 mt-2">
                    <option value="">Pilih Pengajar</option>
                    <?php foreach ($pengajar as $p) : ?>
                        <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filterBulan" class="form-control mr-2 mt-2">
                    <?php foreach (range(1, 12) as $b) : ?>
                        <option value="<?= $b ?>" <?= $b == date('n') ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $b, 10)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="filterTahun" class="form-control mr-2 mt-2">
                    <?php $now = date('Y');
                    for ($i = $now - 2; $i <= $now + 1; $i++) : ?>
                        <option value="<?= $i ?>" <?= $i == $now ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>

                <button class="btn btn-primary mt-2" id="filterBtn">Tampilkan</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="jadwalKosongTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Jam</th>
                            <th>Senin</th>
                            <th>Selasa</th>
                            <th>Rabu</th>
                            <th>Kamis</th>
                            <th>Jumat</th>
                            <th>Sabtu</th>
                            <th>Minggu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada Data</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <table id="jadwalDetailTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Detail Siswa</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script> <!-- Moment.js -->
            <script>
                $('#filterBtn').click(function() {
                    const id_pengajar = $('#filterPengajar').val();
                    const bulan = $('#filterBulan').val();
                    const tahun = $('#filterTahun').val();

                    $.ajax({
                        url: '<?= site_url('Report/get_jadwal_kosong_pengajar') ?>',
                        method: 'POST',
                        data: {
                            id_pengajar,
                            bulan,
                            tahun
                        },
                        dataType: 'json',
                        success: function(data) {
                            let html = '';
                            let uniqueJam = [];

                            // Ambil kombinasi jam unik
                            data.forEach((jadwal) => {
                                if (!uniqueJam.some(item => item.jam_mulai === jadwal.jam_mulai && item.jam_selesai === jadwal.jam_selesai)) {
                                    uniqueJam.push({
                                        jam_mulai: jadwal.jam_mulai,
                                        jam_selesai: jadwal.jam_selesai
                                    });
                                }
                            });

                            // Loop setiap jam
                            uniqueJam.forEach((jam) => {
                                let row = `<tr><td>${jam.jam_mulai} - ${jam.jam_selesai}</td>`;
                                let terpakai = {
                                    'Senin': null,
                                    'Selasa': null,
                                    'Rabu': null,
                                    'Kamis': null,
                                    'Jumat': null,
                                    'Sabtu': null,
                                    'Minggu': null
                                };

                                // Kumpulkan status per hari
                                data.forEach((jadwal) => {
                                    if (jadwal.jam_mulai === jam.jam_mulai && jadwal.jam_selesai === jam.jam_selesai) {
                                        if (terpakai.hasOwnProperty(jadwal.hari)) {
                                            // Tentukan warna badge berdasarkan status_bayar
                                            // let badgeClass = "badge-info"; // Default biru
                                            // if (jadwal.status_bayar == -1) {
                                            //     badgeClass = "badge-warning"; // Kuning kalau status -1
                                            // }

                                            var element = '<button class="badge badge-info btn-jadwal-detail" data-jadwal="' + jadwal.nama_peserta + '" data-status-bayar ="' + jadwal.status_bayar + '"style="cursor: pointer;">Detail Siswa</button>';
                                            terpakai[jadwal.hari] = jadwal.terpakai ? element : null;
                                        }
                                    }
                                });

                                // Buat kolom per hari
                                for (let hari in terpakai) {
                                    if (terpakai[hari]) {
                                        row += `<td class="text-danger"><center><small>${terpakai[hari]}</small></center></td>`;
                                    } else {
                                        row += `<td class="text-success"><center>Kosong</center></td>`;
                                    }
                                }

                                html += row + '</tr>';
                            });

                            $('#jadwalKosongTable tbody').html(html);
                        }
                    });

                    function lihatDetail(data, status_bayar) {
                        var html = '';
                        var result_data = data.split(',');


                        result_data.forEach(function(r) {
                            console.log(r)
                            var match = r.match(/\(([^)]+)\)/);
                            let noWa = match ? match[1] : '';
                            var wa_web = 'https://wa.me/' + noWa;
                            var warna = '';
                            if (status_bayar == -1) {
                                warna = 'text-warning';
                            }
                            var buttonWa = "<a class='btn btn-link btn-success' title='Kirim Pesan WA' href=" + 'https://wa.me/' + noWa + " target='_blank'><i class='fa fa-comment-dots'></i></a>";
                            html += '<tr>';
                            html += '<td><span class=' + warna + '>' + r + ' ' + buttonWa + '</td>';
                            html += '</tr>';
                        });
                        $('#jadwalDetailTable tbody').html(html);
                        $('#detailModal').modal('show');
                    }

                    $(document).on('click', '.btn-jadwal-detail', function() {
                        var jadwal = $(this).data('jadwal');
                        var status_bayar = $(this).data('status-bayar');
                        lihatDetail(jadwal, status_bayar);
                    });
                });
            </script>
        </div>