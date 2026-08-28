  <!-- Content Header (Page header) -->
  <section class="content-header">
      <h1>
          MasterData
      </h1>
  </section>

  <!-- Main content -->
  <section class="content">
      <div class='row'>
          <div class='col-md-12'>
              <div class='box '>
                  <div class='box-header'>
                      <h3 class='box-title'>Edit Data Pengguna </h3>
                  </div><!-- /.box-header -->
                  <div class='box-body pad'>
                      <div class="modal-body">
                          <form id="tambah-pengguna">
                              <div class="form-group">
                                  <label for="exampleFormControlInput1">Username</label>
                                  <input type="hidden" class="form-control" id="tipe_form" value="edit">
                                  <input type="hidden" class="form-control" id="id_user" value="<?php echo $data_pengguna[0]->id_user; ?>">
                                  <input type="text" class="form-control" id="username" placeholder="" value="<?php echo $data_pengguna[0]->username; ?>" required>
                              </div>
                              <div class="form-group">
                                  <label for="exampleFormControlInput1">Password</label>
                                  <input type="password" class="form-control" id="password" placeholder="Kosongkan, jika tidak ada perubahan" value="">
                              </div>
                              <div class="form-group">
                                  <label for="exampleFormControlSelect1">Jabatan</label>
                                  <select class="form-control" id="jabatan">
                                      <?php if ($this->session->userdata('jabatan') == 'superadmin') { ?>
                                          <option value='superadmin' <?php echo $data_pengguna[0]->jabatan == "superadmin" ? 'selected' : ''; ?>>Super Admin
                                          </option>
                                          <option value='koordinator' <?php echo $data_pengguna[0]->jabatan == "koordinator" ? 'selected' : ''; ?>>Koordinator
                                          </option>
                                      <?php } ?>
                                      <option value='relawan' <?php echo $data_pengguna[0]->jabatan == "relawan" ? 'selected' : ''; ?>>Relawan
                                      </option>
                                      <option value='saksi' <?php echo $data_pengguna[0]->jabatan == "saksi" ? 'selected' : ''; ?>>Saksi
                                      </option>
                                  </select>
                              </div>
                              <div class="form-group">
                                  <label for="exampleFormControlInput1">Nama</label>
                                  <input type="text" class="form-control" id="nama" placeholder="" value="<?php echo $data_pengguna[0]->nama; ?>" required>
                              </div>
                              <div class="form-group">
                                  <label for="exampleFormControlSelect1">Jenis Kelamin</label>
                                  <select class="form-control" id="jk" required>
                                      <option value='L' <?php echo $data_pengguna[0]->jk == "L" ? 'selected' : ''; ?>>Laki-Laki</option>
                                      <option value='P' <?php echo $data_pengguna[0]->jk == "P" ? 'selected' : ''; ?>>Perempuan</option>
                                  </select>
                              </div>
                              <div class="form-group">
                                  <label for="exampleFormControlInput1">No HP</label>
                                  <input type="text" class="form-control" id="no_hp" placeholder="" value="<?php echo $data_pengguna[0]->no_hp; ?>" required>
                              </div>
                              <div class="row">
                                  <div class="col-sm-12">
                                      <!-- select -->
                                      <div class="form-group">
                                          <label>Kecamatan</label> <small class="text-danger">Setting Wilayah Untuk Koordinator</small>
                                          <select class="form-control js-example-basic-single" style="width:100%" name="id_kec">
                                              <option value=''>Kosong </option>
                                              <?php foreach ($data_kecamatan as $r) { ?>
                                                  <option value='<?= $r->id_kec; ?>' <?= $data_pengguna[0]->id_kec == $r->id_kec ? 'selected' : ''; ?>><?= $r->nama_kec . ' / ' . $r->nama_kab . ' / ' . $r->nama_prov; ?></option>
                                              <?php } ?>
                                          </select>
                                      </div>
                                  </div>
                              </div>
                              <div class="row">
                                  <div class="col-sm-12">
                                      <!-- select -->
                                      <div class="form-group">
                                          <label>Kelurahan</label><small class="text-danger">Setting Wilayah Untuk Relawan Desa</small>
                                          <select class="form-control js-example-basic-single" style="width:100%" name="id_kelurahan">
                                              <option value=''>Kosong </option>
                                              <?php foreach ($data_kelurahan as $r) { ?>
                                                  <option value='<?= $r->id_kel; ?>'><?= $r->nama_kel . ' / ' . $r->nama_kec . ' / ' . $r->nama_kab . ' / ' . $r->nama_prov; ?></option>
                                              <?php } ?>
                                          </select>
                                      </div>
                                  </div>
                              </div>
                              <div class="row">
                                  <div class="col-sm-6">
                                      <div class="form-group">
                                          <label>TPS</label> <small class="text-danger">Setting Wilayah Untuk relawan Tps atau Saksi</small>
                                          <select class="form-control js-example-basic-single" style="width:100%" name="id_tps" id="id_tps">
                                              <option value=''>Kosong </option>
                                              <?php foreach ($data_tps as $r) { ?>
                                                  <option value='<?= $r->id_tps; ?>' <?= $data_pengguna[0]->id_tps == $r->id_tps ? 'selected' : ''; ?>><?= $r->nama_tps . ' / ' . $r->nama_kel . ' / ' . $r->nama_kec . ' / ' . $r->nama_kab; ?></option>
                                              <?php } ?>
                                          </select>
                                      </div>
                                  </div>
                              </div>
                              <div class="form-group" id="id_relawan">
                                  <label for="exampleFormControlSelect1">Relawan</label>
                                  <select class="form-control js-example-basic-single" style="width:100%" name="id_relawan">
                                      <option value=''>Bukan Relawan</option>
                                      <?php foreach ($data_relawan as $r) { ?>
                                          <option value='<?= $r->id_relawan; ?>' <?php if ($data_pengguna[0]->jabatan == 'relawan') {
                                                                                        echo $data_pengguna[0]->id_relawan == $r->id_relawan ? 'selected' : '';
                                                                                    } ?>><?= $r->nama_relawan; ?></option>
                                      <?php } ?>
                                  </select>
                              </div>
                      </div>
                      <div class="modal-footer">

                          <a href="<?php echo site_url('MasterData/pengguna'); ?>"> <button type="button" class="btn btn-secondary">Batal</button></a>
                          <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
                      </div>
                      </form>
                  </div>
              </div><!-- /.box -->
          </div><!-- /.col-->
      </div><!-- ./row -->

      <script>
          $(document).ready(function() {
              var sel = '<?php echo $data_pengguna[0]->id_kel; ?>';
              var is_relawan = '<?php echo $data_pengguna[0]->id_relawan != "" ? true : false; ?>';
              setTimeout(() => {
                  $("select[name='id_kelurahan']").trigger("change");
                  setTimeout(() => {
                      $("select[name='id_kelurahan']").val(sel).trigger("change");
                      if (is_relawan == '1') {
                          $("#id_relawan").show();
                      } else if (is_relawan == '') {
                          $("#id_relawan").hide();
                      }
                  }, 200);
              }, 300);

              $(document).on('change', '#jabatan', function() {
                  $("#id_relawan").val("").change();

                  if ($(this).val() == 'relawan') {
                      $("#id_relawan").show();
                  } else {
                      $("#id_relawan").hide();
                      $("select[name='id_relawan']").val("").change();
                  }

              });

              $('#tambah-pengguna').on('submit', function(event) {
                  event.preventDefault();
                  $.ajax({
                      type: "POST",
                      url: '<?php echo site_url('MasterData/add_pengguna'); ?>',
                      data: {
                          tipe_form: $('#tipe_form').val(),
                          id_user: $('#id_user').val(),
                          nama: $('#nama').val(),
                          jk: $('#jk').val(),
                          no_hp: $('#no_hp').val(),
                          id_tps: $('#id_tps').val(),
                          id_kel: $('select[name="id_kelurahan"]').val(),
                          id_kec: $('select[name="id_kec"]').val(),
                          username: $('#username').val(),
                          password: $('#password').val(),
                          jabatan: $('#jabatan').val(),
                          id_relawan: $('select[name="id_relawan"]').val(),


                      },
                      dataType: "json",
                      success: function(data) {
                          const Toast = Swal.mixin({
                              toast: true,
                              position: 'top-right',
                              showConfirmButton: false,
                              timer: 3000
                          });
                          Toast.fire({
                              type: 'success',
                              title: 'Pengguna Berhasil diupdate'
                          });
                          $('#tambah-pengguna').trigger("reset");
                          setTimeout(function() {
                              window.location.href =
                                  '<?php echo site_url('MasterData/pengguna'); ?>';
                              window.clearTimeout();
                          }, 1000);

                      },
                      error: function(request, status, error) {
                          console.log(request.responseText);
                          const Toast = Swal.mixin({
                              toast: true,
                              position: 'top-right',
                              showConfirmButton: false,
                              timer: 3000
                          });
                          Toast.fire({
                              type: 'error',
                              title: 'Gagal menghubungkan Ke Server'
                          })
                      }

                  });

              })





          })
      </script>