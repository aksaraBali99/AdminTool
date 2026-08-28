<div class="page-inner">
    <div class="col-md-12">
        <!-- Header Card -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Detail Siswa: <?= $siswa->nama_anak ?></h4>
                    <a href="<?= site_url('Peserta/peserta') ?>" class="btn btn-secondary btn-round ml-auto">
                        <i class="fa fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Info Dasar -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Informasi Dasar</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Nama Anak</th>
                                <td><?= $siswa->nama_anak ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Lahir</th>
                                <td><?= $siswa->tgl_lahir_anak ? date('d-m-Y', strtotime($siswa->tgl_lahir_anak)) : '-' ?></td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin</th>
                                <td><?= $siswa->jk == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                            </tr>
                            <tr>
                                <th>Nama Orang Tua</th>
                                <td><?= $siswa->nama_ortu ?></td>
                            </tr>
                            <tr>
                                <th>No HP</th>
                                <td><?= $siswa->no_hp ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?= $siswa->email ?: '-' ?></td>
                            </tr>
                            <tr>
                                <th>Level Sekolah</th>
                                <td><?= $siswa->level_sekolah ?: '-' ?></td>
                            </tr>
                            <tr>
                                <th>Nama Sekolah</th>
                                <td><?= $siswa->nama_sekolah ?: '-' ?></td>
                            </tr>
                            <tr>
                                <th>Jenis Kelas</th>
                                <td><?= $siswa->nama_kelas ?></td>
                            </tr>
                            <tr>
                                <th>Status Siswa</th>
                                <td>
                                    <?php if($siswa->status_siswa == 'Aktif'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php elseif($siswa->status_siswa == 'Non Aktif'): ?>
                                        <span class="badge badge-danger">Non Aktif</span>
                                        <?php if($siswa->alasan_nonaktif): ?>
                                            <br><small>Alasan: <?= $siswa->alasan_nonaktif == 'Lain-lain' ? $siswa->alasan_lainnya : $siswa->alasan_nonaktif ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Cuti</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalUpdateStatus">
                            <i class="fa fa-edit"></i> Update Status
                        </button>
                    </div>
                </div>
            </div>

            <!-- Level Siswa -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Level Siswa</h4>
                            <button class="btn btn-primary btn-sm ml-auto" data-toggle="modal" data-target="#modalUpdateLevel">
                                <i class="fa fa-level-up-alt"></i> Update Level
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($level_aktif): ?>
                            <div class="alert alert-success">
                                <h5 class="mb-1"><strong>Level Saat Ini: <?= $level_aktif->nama_level ?></strong></h5>
                                <small>Sejak: <?= date('d-m-Y', strtotime($level_aktif->tanggal_kenaikan_level)) ?></small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <strong>Belum ada level</strong>
                                <br><small>Silakan set level awal untuk siswa ini</small>
                            </div>
                        <?php endif; ?>

                        <h6 class="mt-3"><strong>Riwayat Level</strong></h6>
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>Mulai</th>
                                    <th>Selesai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($riwayat_level)): ?>
                                    <tr><td colspan="4" class="text-center">Belum ada riwayat</td></tr>
                                <?php else: ?>
                                    <?php foreach($riwayat_level as $r): ?>
                                    <tr>
                                        <td><?= $r->nama_level ?></td>
                                        <td><?= date('d-m-Y', strtotime($r->tanggal_kenaikan_level)) ?></td>
                                        <td><?= $r->tanggal_selesai ? date('d-m-Y', strtotime($r->tanggal_selesai)) : '-' ?></td>
                                        <td>
                                            <?php if($r->is_aktif): ?>
                                                <span class="badge badge-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-danger btn-xs btn-hapus-level" data-id="<?= $r->id ?>" title="Hapus">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Riwayat Ujian -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Riwayat Ujian</h4>
                            <div class="ml-auto">
                                <a href="<?= site_url('Siswa/export_ujian_pdf/'.$siswa->id_peserta) ?>" target="_blank" class="btn btn-info btn-sm mr-2">
                                    <i class="fa fa-file-pdf"></i> Export PDF
                                </a>
                                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalInputUjian">
                                    <i class="fa fa-plus"></i> Input Ujian
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis Ujian</th>
                                    <th>Nilai</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($riwayat_ujian)): ?>
                                    <tr><td colspan="6" class="text-center">Belum ada riwayat ujian</td></tr>
                                <?php else: ?>
                                    <?php foreach($riwayat_ujian as $u): 
                                        $nilai = floatval($u->nilai_ujian);
                                        if ($nilai >= 90) {
                                            $grade_label = 'Excellent';
                                            $grade_class = 'badge-success';
                                        } elseif ($nilai >= 80) {
                                            $grade_label = 'Very Good';
                                            $grade_class = 'badge-primary';
                                        } elseif ($nilai >= 70) {
                                            $grade_label = 'Good';
                                            $grade_class = 'badge-info';
                                        } elseif ($nilai >= 60) {
                                            $grade_label = 'Fair';
                                            $grade_class = 'badge-warning';
                                        } else {
                                            $grade_label = 'Poor';
                                            $grade_class = 'badge-danger';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= date('d-m-Y', strtotime($u->tanggal_ujian)) ?></td>
                                        <td><?= $u->jenis_ujian ?></td>
                                        <td><strong><?= $u->nilai_ujian ?></strong></td>
                                        <td><span class="badge <?= $grade_class ?>"><?= $grade_label ?></span></td>
                                        <td><?= $u->catatan_ujian ?: '-' ?></td>
                                        <td>
                                            <a href="<?= site_url('Siswa/print_ujian/'.$u->id) ?>" target="_blank" class="btn btn-info btn-xs" title="Print">
                                                <i class="fa fa-print"></i>
                                            </a>
                                            <button class="btn btn-warning btn-xs btn-edit-ujian" data-id="<?= $u->id ?>" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-xs btn-hapus-ujian" data-id="<?= $u->id ?>" title="Hapus">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sertifikat -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Sertifikat</h4>
                            <button class="btn btn-primary btn-sm ml-auto" data-toggle="modal" data-target="#modalGenerateSertifikat">
                                <i class="fa fa-certificate"></i> Generate Sertifikat
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Level</th>
                                    <th>Tanggal Terbit</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($sertifikat)): ?>
                                    <tr><td colspan="4" class="text-center">Belum ada sertifikat</td></tr>
                                <?php else: ?>
                                    <?php foreach($sertifikat as $s): ?>
                                    <tr>
                                        <td><?= $s->nomor_sertifikat ?></td>
                                        <td><?= $s->nama_level ?></td>
                                        <td><?= date('d-m-Y', strtotime($s->tanggal_terbit)) ?></td>
                                        <td>
                                            <a href="<?= site_url('Siswa/print_sertifikat/'.$s->id) ?>" target="_blank" class="btn btn-info btn-xs">
                                                <i class="fa fa-print"></i> Print
                                            </a>
                                            <button class="btn btn-danger btn-xs btn-hapus-sertifikat" data-id="<?= $s->id ?>" title="Hapus">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Absensi -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Absensi Siswa</h4>
                    <button class="btn btn-primary btn-sm ml-auto mr-2" data-toggle="modal" data-target="#modalInputAbsensi">
                        <i class="fa fa-plus"></i> Input Absensi
                    </button>
                    <a href="javascript:void(0)" onclick="downloadAbsensiPDF()" class="btn btn-info btn-sm">
                        <i class="fa fa-download"></i> Download PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Dari Tanggal</label>
                        <input type="date" id="filter_date_from" class="form-control" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Sampai Tanggal</label>
                        <input type="date" id="filter_date_to" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block" id="btnFilterAbsensi">
                            <i class="fa fa-search"></i> Filter
                        </button>
                    </div>
                </div>
                <div id="rekap-absensi" class="mb-3"></div>
                <table class="table table-sm table-bordered" id="table-absensi">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-absensi">
                        <tr><td colspan="3" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Update Level -->
<div class="modal fade" id="modalUpdateLevel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formUpdateLevel">
                <div class="modal-header">
                    <h5 class="modal-title">Update Level Siswa</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_siswa" value="<?= $siswa->id_peserta ?>">
                    <div class="form-group">
                        <label>Level Baru</label>
                        <select name="id_level" class="form-control" required>
                            <option value="">-- Pilih Level --</option>
                            <?php foreach($all_level as $l): ?>
                                <option value="<?= $l->id_level ?>"><?= $l->nama_level ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Input Ujian -->
<div class="modal fade" id="modalInputUjian" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formInputUjian">
                <div class="modal-header">
                    <h5 class="modal-title">Input Data Ujian - Student's Performance</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_siswa" value="<?= $siswa->id_peserta ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Ujian</label>
                                <input type="date" name="tanggal_ujian" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jenis Ujian</label>
                                <select name="jenis_ujian" class="form-control" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="Placement Test">Placement Test</option>
                                    <option value="Ujian Naik Level">Ujian Naik Level</option>
                                    <!-- <option value="Mid Test">Mid Test</option>
                                    <option value="Final Test">Final Test</option> -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h6><strong>Student's Performance</strong></h6>
                    <div class="alert alert-info py-2">
                        <small>
                            <strong>Grading:</strong> A = Excellent (90-100) | B = Very Good (80-89) | C = Good (70-79) | D = Fair (60-69) | E = Poor (below 60)
                        </small>
                    </div>
                    
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th width="40%">SKILL</th>
                                <th width="30%">SCORE (0-100)</th>
                                <th width="30%">GRADE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Vocabulary</strong></td>
                                <td><input type="number" name="nilai_vocabulary" class="form-control score-input" min="0" max="100" step="0.01" required></td>
                                <td class="grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr>
                                <td><strong>Grammar</strong></td>
                                <td><input type="number" name="nilai_grammar" class="form-control score-input" min="0" max="100" step="0.01" required></td>
                                <td class="grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr>
                                <td><strong>Speaking</strong></td>
                                <td><input type="number" name="nilai_speaking" class="form-control score-input" min="0" max="100" step="0.01" required></td>
                                <td class="grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr>
                                <td><strong>Writing</strong></td>
                                <td><input type="number" name="nilai_writing" class="form-control score-input" min="0" max="100" step="0.01" required></td>
                                <td class="grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr>
                                <td><strong>Listening</strong></td>
                                <td><input type="number" name="nilai_listening" class="form-control score-input" min="0" max="100" step="0.01" required></td>
                                <td class="grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>AVERAGE</strong></td>
                                <td><input type="text" name="nilai_ujian" id="nilai_rata_rata" class="form-control" readonly></td>
                                <td id="grade_rata_rata"><span class="badge badge-secondary">-</span></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="catatan_ujian" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Ujian -->
<div class="modal fade" id="modalEditUjian" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditUjian">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Ujian</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id_ujian">
                    <input type="hidden" name="id_siswa" value="<?= $siswa->id_peserta ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Ujian</label>
                                <input type="date" name="tanggal_ujian" id="edit_tanggal_ujian" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jenis Ujian</label>
                                <select name="jenis_ujian" id="edit_jenis_ujian" class="form-control" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="Placement Test">Placement Test</option>
                                    <option value="Ujian Naik Level">Ujian Naik Level</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h6><strong>Student's Performance</strong></h6>
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th width="40%">SKILL</th>
                                <th width="30%">SCORE (0-100)</th>
                                <th width="30%">GRADE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Vocabulary</strong></td>
                                <td><input type="number" name="nilai_vocabulary" id="edit_nilai_vocabulary" class="form-control edit-score-input" min="0" max="100" step="0.01" required></td>
                                <td class="edit-grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr>
                                <td><strong>Grammar</strong></td>
                                <td><input type="number" name="nilai_grammar" id="edit_nilai_grammar" class="form-control edit-score-input" min="0" max="100" step="0.01" required></td>
                                <td class="edit-grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr>
                                <td><strong>Speaking</strong></td>
                                <td><input type="number" name="nilai_speaking" id="edit_nilai_speaking" class="form-control edit-score-input" min="0" max="100" step="0.01" required></td>
                                <td class="edit-grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr>
                                <td><strong>Writing</strong></td>
                                <td><input type="number" name="nilai_writing" id="edit_nilai_writing" class="form-control edit-score-input" min="0" max="100" step="0.01" required></td>
                                <td class="edit-grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr>
                                <td><strong>Listening</strong></td>
                                <td><input type="number" name="nilai_listening" id="edit_nilai_listening" class="form-control edit-score-input" min="0" max="100" step="0.01" required></td>
                                <td class="edit-grade-display"><span class="badge badge-secondary">-</span></td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>AVERAGE</strong></td>
                                <td><input type="text" name="nilai_ujian" id="edit_nilai_rata_rata" class="form-control" readonly></td>
                                <td id="edit_grade_rata_rata"><span class="badge badge-secondary">-</span></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="catatan_ujian" id="edit_catatan_ujian" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Input Absensi -->
<div class="modal fade" id="modalInputAbsensi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formInputAbsensi">
                <div class="modal-header">
                    <h5 class="modal-title">Input Absensi</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_siswa" value="<?= $siswa->id_peserta ?>">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Status Kehadiran</label>
                        <select name="status_hadir" class="form-control" required>
                            <option value="Hadir">Hadir</option>
                            <option value="Izin">Izin</option>
                            <option value="Alpha">Alpha</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Generate Sertifikat -->
<div class="modal fade" id="modalGenerateSertifikat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formGenerateSertifikat">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Sertifikat</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_siswa" value="<?= $siswa->id_peserta ?>">
                    
                    <div class="form-group">
                        <label>Tanggal Terbit/Kelulusan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_terbit" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Level Kelulusan <span class="text-danger">*</span></label>
                        <select name="id_level" class="form-control" required>
                            <option value="">-- Pilih Level --</option>
                            <?php foreach($all_level as $l): ?>
                                <option value="<?= $l->id_level ?>" <?= ($level_aktif && $level_aktif->id_level == $l->id_level) ? 'selected' : '' ?>>
                                    <?= $l->nama_level ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if($level_aktif): ?>
                            <small class="text-muted">Level aktif siswa: <strong><?= $level_aktif->nama_level ?></strong></small>
                        <?php else: ?>
                            <small class="text-warning">Siswa belum memiliki level aktif</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Guru Pengajar <span class="text-danger">*</span></label>
                        <select name="id_guru" class="form-control" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach($pengajar as $p): ?>
                                <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Update Status -->
<div class="modal fade" id="modalUpdateStatus" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formUpdateStatus">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status Siswa</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_siswa" value="<?= $siswa->id_peserta ?>">
                    <div class="form-group">
                        <label>Status Siswa</label>
                        <select name="status_siswa" id="status_siswa" class="form-control" required>
                            <option value="Aktif" <?= $siswa->status_siswa == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Non Aktif" <?= $siswa->status_siswa == 'Non Aktif' ? 'selected' : '' ?>>Non Aktif</option>
                            <option value="Cuti" <?= $siswa->status_siswa == 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                        </select>
                    </div>
                    <div id="alasan-container" style="display: <?= $siswa->status_siswa == 'Non Aktif' ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label>Alasan Non Aktif</label>
                            <select name="alasan_nonaktif" id="alasan_nonaktif" class="form-control">
                                <option value="">-- Pilih Alasan --</option>
                                <option value="Sibuk sekolah" <?= $siswa->alasan_nonaktif == 'Sibuk sekolah' ? 'selected' : '' ?>>Sibuk sekolah</option>
                                <option value="Tidak ada kemajuan" <?= $siswa->alasan_nonaktif == 'Tidak ada kemajuan' ? 'selected' : '' ?>>Tidak ada kemajuan</option>
                                <option value="Tidak suka guru" <?= $siswa->alasan_nonaktif == 'Tidak suka guru' ? 'selected' : '' ?>>Tidak suka guru</option>
                                <option value="Tidak cocok jadwal" <?= $siswa->alasan_nonaktif == 'Tidak cocok jadwal' ? 'selected' : '' ?>>Tidak cocok jadwal</option>
                                <option value="Pindah rumah" <?= $siswa->alasan_nonaktif == 'Pindah rumah' ? 'selected' : '' ?>>Pindah rumah</option>
                                <option value="Lain-lain" <?= $siswa->alasan_nonaktif == 'Lain-lain' ? 'selected' : '' ?>>Lain-lain</option>
                            </select>
                        </div>
                        <div class="form-group" id="alasan-lainnya-container" style="display: <?= $siswa->alasan_nonaktif == 'Lain-lain' ? 'block' : 'none' ?>;">
                            <label>Alasan Lainnya</label>
                            <textarea name="alasan_lainnya" class="form-control" rows="2"><?= $siswa->alasan_lainnya ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div> 

<script>
$(document).ready(function() {
    const id_siswa = <?= $siswa->id_peserta ?>;
    
    // Load absensi on page load
    loadAbsensi();
    
    function loadAbsensi() {
        $.post('<?= site_url("Siswa/get_absensi") ?>', {
            id_siswa: id_siswa,
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        }, function(res) {
            var data = JSON.parse(res);
            var html = '';
            
            if (data.absensi.length === 0) {
                html = '<tr><td colspan="3" class="text-center">Tidak ada data absensi</td></tr>';
            } else {
                data.absensi.forEach(function(a) {
                    var badge = a.status_hadir === 'Hadir' ? 'success' : (a.status_hadir === 'Izin' ? 'warning' : 'danger');
                    html += '<tr>';
                    html += '<td>' + formatDate(a.tanggal) + '</td>';
                    html += '<td><span class="badge badge-' + badge + '">' + a.status_hadir + '</span></td>';
                    html += '<td>' + (a.keterangan || '-') + '</td>';
                    html += '<td><button class="btn btn-danger btn-xs btn-hapus-absensi" data-id="' + a.id + '" title="Hapus"><i class="fa fa-trash"></i></button></td>';
                    html += '</tr>';
                });
            }
            
            $('#tbody-absensi').html(html);
            
            // Rekap
            var rekap = data.rekap;
            var rekapHtml = '<div class="row">';
            rekapHtml += '<div class="col-md-3"><div class="alert alert-success"><strong>' + (rekap.total_hadir || 0) + '</strong> Hadir</div></div>';
            rekapHtml += '<div class="col-md-3"><div class="alert alert-warning"><strong>' + (rekap.total_izin || 0) + '</strong> Izin</div></div>';
            rekapHtml += '<div class="col-md-3"><div class="alert alert-danger"><strong>' + (rekap.total_alpha || 0) + '</strong> Alpha</div></div>';
            rekapHtml += '<div class="col-md-3"><div class="alert alert-info"><strong>' + (rekap.total_hari || 0) + '</strong> Total Hari</div></div>';
            rekapHtml += '</div>';
            $('#rekap-absensi').html(rekapHtml);
        });
    }
    
    function formatDate(dateStr) {
        var d = new Date(dateStr);
        return d.getDate().toString().padStart(2, '0') + '-' + 
               (d.getMonth() + 1).toString().padStart(2, '0') + '-' + 
               d.getFullYear();
    }
    
    $('#btnFilterAbsensi').click(function() {
        loadAbsensi();
    });
    
    // Form submissions
    $('#formUpdateLevel').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Siswa/update_level") ?>', $(this).serialize(), function(res) {
            var data = JSON.parse(res);
            if (data.status === 'success') {
                Swal.fire('Sukses', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
    
    $('#formInputUjian').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Siswa/input_ujian") ?>', $(this).serialize(), function(res) {
            var data = JSON.parse(res);
            if (data.status === 'success') {
                Swal.fire('Sukses', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
    
    $('#formInputAbsensi').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Siswa/input_absensi") ?>', $(this).serialize(), function(res) {
            var data = JSON.parse(res);
            if (data.status === 'success') {
                Swal.fire('Sukses', data.message, 'success');
                $('#modalInputAbsensi').modal('hide');
                loadAbsensi();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
    
    $('#formGenerateSertifikat').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Siswa/generate_sertifikat") ?>', $(this).serialize(), function(res) {
            var data = JSON.parse(res);
            if (data.status === 'success') {
                Swal.fire('Sukses', 'Sertifikat berhasil digenerate: ' + data.nomor, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
    
    $('#formUpdateStatus').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Siswa/update_status") ?>', $(this).serialize(), function(res) {
            var data = JSON.parse(res);
            if (data.status === 'success') {
                Swal.fire('Sukses', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
    
    // Status toggle
    $('#status_siswa').change(function() {
        if ($(this).val() === 'Non Aktif') {
            $('#alasan-container').show();
        } else {
            $('#alasan-container').hide();
        }
    });
    
    $('#alasan_nonaktif').change(function() {
        if ($(this).val() === 'Lain-lain') {
            $('#alasan-lainnya-container').show();
        } else {
            $('#alasan-lainnya-container').hide();
        }
    });
    
    // Auto calculate grade from score
    function getGrade(score) {
        if (score >= 90) return { grade: 'A', label: 'Excellent', class: 'badge-success' };
        if (score >= 80) return { grade: 'B', label: 'Very Good', class: 'badge-primary' };
        if (score >= 70) return { grade: 'C', label: 'Good', class: 'badge-info' };
        if (score >= 60) return { grade: 'D', label: 'Fair', class: 'badge-warning' };
        return { grade: 'E', label: 'Poor', class: 'badge-danger' };
    }
    
    function calculateGrades() {
        var scores = [];
        $('.score-input').each(function(index) {
            var val = parseFloat($(this).val()) || 0;
            var gradeInfo = getGrade(val);
            var gradeCell = $(this).closest('tr').find('.grade-display');
            
            if ($(this).val() !== '') {
                gradeCell.html('<span class="badge ' + gradeInfo.class + '">' + gradeInfo.grade + ' - ' + gradeInfo.label + '</span>');
                scores.push(val);
            } else {
                gradeCell.html('<span class="badge badge-secondary">-</span>');
            }
        });
        
        // Calculate average
        if (scores.length > 0) {
            var avg = scores.reduce((a, b) => a + b, 0) / scores.length;
            var avgRounded = Math.round(avg * 100) / 100;
            $('#nilai_rata_rata').val(avgRounded);
            
            var avgGrade = getGrade(avgRounded);
            $('#grade_rata_rata').html('<span class="badge ' + avgGrade.class + '">' + avgGrade.grade + ' - ' + avgGrade.label + '</span>');
        } else {
            $('#nilai_rata_rata').val('');
            $('#grade_rata_rata').html('<span class="badge badge-secondary">-</span>');
        }
    }
    
    // Calculate grades for edit modal
    function calculateEditGrades() {
        var scores = [];
        $('.edit-score-input').each(function(index) {
            var val = parseFloat($(this).val()) || 0;
            var gradeInfo = getGrade(val);
            var gradeCell = $(this).closest('tr').find('.edit-grade-display');
            
            if ($(this).val() !== '') {
                gradeCell.html('<span class="badge ' + gradeInfo.class + '">' + gradeInfo.grade + ' - ' + gradeInfo.label + '</span>');
                scores.push(val);
            } else {
                gradeCell.html('<span class="badge badge-secondary">-</span>');
            }
        });
        
        if (scores.length > 0) {
            var avg = scores.reduce((a, b) => a + b, 0) / scores.length;
            var avgRounded = Math.round(avg * 100) / 100;
            $('#edit_nilai_rata_rata').val(avgRounded);
            
            var avgGrade = getGrade(avgRounded);
            $('#edit_grade_rata_rata').html('<span class="badge ' + avgGrade.class + '">' + avgGrade.grade + ' - ' + avgGrade.label + '</span>');
        } else {
            $('#edit_nilai_rata_rata').val('');
            $('#edit_grade_rata_rata').html('<span class="badge badge-secondary">-</span>');
        }
    }
    
    $(document).on('input', '.score-input', function() {
        calculateGrades();
    });
    
    $(document).on('input', '.edit-score-input', function() {
        calculateEditGrades();
    });
    
    // Edit ujian button
    $(document).on('click', '.btn-edit-ujian', function() {
        var id = $(this).data('id');
        $.post('<?= site_url("Siswa/get_ujian_by_id") ?>', { id: id }, function(res) {
            var data = JSON.parse(res);
            $('#edit_id_ujian').val(data.id);
            $('#edit_tanggal_ujian').val(data.tanggal_ujian);
            $('#edit_jenis_ujian').val(data.jenis_ujian);
            $('#edit_nilai_vocabulary').val(data.nilai_vocabulary);
            $('#edit_nilai_grammar').val(data.nilai_grammar);
            $('#edit_nilai_speaking').val(data.nilai_speaking);
            $('#edit_nilai_writing').val(data.nilai_writing);
            $('#edit_nilai_listening').val(data.nilai_listening);
            $('#edit_nilai_rata_rata').val(data.nilai_ujian);
            $('#edit_catatan_ujian').val(data.catatan_ujian);
            calculateEditGrades();
            $('#modalEditUjian').modal('show');
        });
    });
    
    // Submit edit form
    $('#formEditUjian').submit(function(e) {
        e.preventDefault();
        $.post('<?= site_url("Siswa/update_ujian") ?>', $(this).serialize(), function(res) {
            var data = JSON.parse(res);
            if (data.status === 'success') {
                Swal.fire('Sukses', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
    
    // Hapus ujian button
    $(document).on('click', '.btn-hapus-ujian', function() {
        var id = $(this).data('id');
       
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: 'Apakah Anda yakin ingin menghapus data ujian ini?', 
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
           if (result.value) {
                console.log(id);
                $.ajax({
                    url: '<?= site_url("Siswa/hapus_ujian") ?>',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            Swal.fire('Terhapus', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Error:', xhr.responseText);
                        Swal.fire('Error', 'Terjadi kesalahan: ' + error, 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-hapus-absensi', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Absensi?',
            text: 'Data absensi ini akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Siswa/hapus_absensi") ?>', {id: id}, function(res) {
                    var data = JSON.parse(res);
                    if (data.status === 'success') {
                        Swal.fire('Berhasil', data.message, 'success');
                        loadAbsensi();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-hapus-sertifikat', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Sertifikat?',
            text: 'Data sertifikat ini akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Siswa/hapus_sertifikat") ?>', {id: id}, function(res) {
                    var data = JSON.parse(res);
                    if (data.status === 'success') {
                        Swal.fire('Berhasil', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-hapus-level', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Riwayat Level?',
            text: 'Jika yang dihapus adalah level aktif, maka level sebelumnya akan menjadi aktif kembali.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.post('<?= site_url("Siswa/hapus_level") ?>', {id: id, id_siswa: id_siswa}, function(res) {
                    var data = JSON.parse(res);
                    if (data.status === 'success') {
                        Swal.fire('Berhasil', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    });
});

function downloadAbsensiPDF() {
    var dateFrom = document.getElementById('filter_date_from').value;
    var dateTo = document.getElementById('filter_date_to').value;
    var url = '<?= site_url("Siswa/download_absensi/".$siswa->id_peserta) ?>?date_from=' + dateFrom + '&date_to=' + dateTo;
    window.open(url, '_blank');
}
</script>
