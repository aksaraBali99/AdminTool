 <div class="page-inner">
     <div class="col-md-12">
         <div class="card">
             <div class="card-header">
                 <div class="d-flex align-items-center">
                     <h4 class="card-title">Pengelolaan Jenis Kelas</h4>
                     <button class="btn btn-primary btn-round ml-auto btn-add-jenis_kelas" data-toggle="modal">
                         <i class="fa fa-plus mr-2"></i>
                         Add Row
                     </button>
                 </div>

             </div>
             <div class='box-body pad'>
                 <div class="card-body">
                     <div class="table-responsive">
                         <table class="table table-bordered" id="jenis_kelas-table" width="100%" cellspacing="0">
                             <thead>
                                 <tr>
                                     <th></th>
                                     <th>Nama Kelas</th>
                                     <th>Gender</th>
                                     <th>Usia</th>
                                     <th>Tipe</th>
                                     <th>Biaya Kelas</th>
                                     <th>Biaya Registrasi</th>
                                     <th>Biaya Buku</th>
                                     <th>Nama Buku</th>
                                     <th>Partnership</th>
                                     <th>
                                         <center>Fungsi</center>
                                     </th>
                                 </tr>
                             </thead>
                             <tbody>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>
         </div><!-- /.box -->
     </div><!-- /.col-->
 </div><!-- ./row -->


 <!-- Modal tambah data -->
 <div class="modal fade m-add-jenis_kelas" id="add-jenis_kelas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="card-title" id="exampleModalLabel"><span id="label_tipe">Tambah</span> Jenis Kelas</h5>
                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>

             <div class="modal-body">
                 <form id="tambah-jenis_kelas" enctype="multipart/form-data">
                     <div class="form-group">
                         <label for="exampleFormControlInput1">Nama Kelas</label>
                         <input type="hidden" class="form-control" name="tipe_form" value="add">
                         <input type="hidden" class="form-control" name="id_jenis_kelas">
                         <input type="text" class="form-control" name="nama_kelas" placeholder="" required>
                     </div>
                     <div class="form-group">
                         <label for="exampleFormControlInput1">Gender</label>
                         <select class="form-control" name="gender" required>
                             <option value='All'>Semua Gender</option>
                             <option value='L'>Laki-Laki</option>
                             <option value='P'>Perempuan</option>
                         </select>
                     </div>
                     <div class="form-group">
                         <label for="exampleFormControlInput1">Usia</label>
                         <select class="form-control" name="usia" required>
                             <option value='All'>Semua Usia</option>
                             <option value='Dewasa'>Dewasa</option>
                             <option value='Anak'>Anak</option>
                         </select>
                     </div>
                     <div class="form-group">
                         <label for="exampleFormControlInput1">Tipe</label>
                         <select class="form-control" name="tipe" required>
                             <option value='Private'>Private</option>
                             <option value='Reguler'>Reguler</option>
                         </select>
                     </div>
                     <div class="form-group">
                         <label for="exampleFormControlInput1">Biaya Kelas</label>
                         <input type="number" class="form-control" name="biaya" placeholder="" required>
                     </div>
                     <div class="form-group">
                         <label for="exampleFormControlInput1">Biaya Registrasi</label>
                         <input type="number" class="form-control" name="biaya_regis" placeholder="" required>
                     </div>
                     <div class="form-group">
                         <label for="exampleFormControlInput1">Biaya Buku</label>
                         <input type="number" class="form-control" name="biaya_buku" placeholder="" required>
                     </div>
                     <div class="form-group">
                         <label for="exampleFormControlInput1">Nama Buku</label>
                         <input type="text" class="form-control" name="nama_buku" placeholder="" required>
                     </div>

                     <!-- Partnership Checkbox -->
                     <div class="form-group">
                         <label class="d-flex align-items-center" style="cursor: pointer;">
                             <input type="checkbox" name="is_partnership" id="is_partnership" value="1" style="width: 18px; height: 18px; margin-right: 10px;">
                             <strong>Partnership Sekolah/Organisasi</strong>
                         </label>
                     </div>

                     <!-- Partnership Fields (Hidden by default) -->
                     <div id="partnership-fields" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 10px;">
                         <h6 class="mb-3"><strong>Data Partnership</strong></h6>
                         <div class="form-group">
                             <label>Nama Organisasi</label>
                             <input type="text" class="form-control" name="nama_organisasi" placeholder="Nama sekolah/organisasi">
                         </div>
                         <div class="form-group">
                             <label>Kontak</label>
                             <input type="text" class="form-control" name="kontak_partnership" placeholder="Nama kontak person">
                         </div>
                         <div class="form-group">
                             <label>Alamat</label>
                             <textarea class="form-control" name="alamat_partnership" rows="2" placeholder="Alamat organisasi"></textarea>
                         </div>
                         <div class="form-group">
                             <label>No Telpon</label>
                             <input type="text" class="form-control" name="no_telp_partnership" placeholder="Nomor telepon">
                         </div>
                         <div class="form-group">
                             <label>Jumlah Siswa</label>
                             <input type="number" class="form-control" name="jumlah_siswa_partnership" placeholder="Jumlah siswa">
                         </div>
                     </div>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                 <button type="submit" class="btn btn-primary">Simpan Kelas</button>
             </div>
             </form>
         </div>
     </div>

     <script type="text/javascript">
         $(document).ready(function() {
             var base_image_url = '<?php echo base_url('uploads/foto/'); ?>'
             var role = '<?php echo $_SESSION['jabatan']; ?>';
             console.log(role)

             var table = $('#jenis_kelas-table').DataTable({
                 "ajax": {
                     url: '<?php echo site_url("MasterData/jenis_kelas_page"); ?>',
                     type: 'POST'
                 },
                 "columnDefs": [{
                         "targets": [0],
                         "visible": false,
                         "searchable": false
                     },

                     {
                         "data": [0],
                         "targets": -1,
                         "render": function(data, type, row, meta) {
                             return "<center><button type='button' data-toggle='tooltip' class='btn btn-link btn-primary btn-lg btn-edit-jenis_kelas' data-original-title='Edit Data' id=" + data + "> <i class='fa fa-edit'></i></button></a> <button type='button' data-toggle='tooltip' class='btn btn-link btn-danger btn-lg' data-original-title='Hapus Data' name='hapus' id=" +
                                 data + " onclick='hapus(`" + data +
                                 "`)'><i class='fa fa-times'></i>  </button></center>";

                         }
                     }
                 ]
             });


             $('#tambah-jenis_kelas').on('submit', function(event) {
                 event.preventDefault();
                 $.ajax({
                     type: "POST",
                     url: '<?php echo site_url('MasterData/add_jenis_kelas'); ?>',
                     data: new FormData(this),
                     contentType: false,
                     cache: false,
                     processData: false,
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
                             title: 'Jenis Kelas Berhasil disimpan'
                         });
                         $('#tambah-jenis_kelas').trigger("reset");
                         setTimeout(function() {
                             window.location.href =
                                 '<?php echo site_url('MasterData/jenis_kelas'); ?>';
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

             });

             $(document).on('click', '.btn-add-jenis_kelas', function() {
                 $(".m-add-jenis_kelas").modal("show");
                 $("input[name='id_jenis_kelas']").val('');
                 $("input[name='tipe_form']").val('add');
                 $('form')[0].reset();
                 $('#partnership-fields').hide();
                 $('#is_partnership').prop('checked', false);
             });

             // Toggle partnership fields
             $('#is_partnership').change(function() {
                 if ($(this).is(':checked')) {
                     $('#partnership-fields').slideDown();
                 } else {
                     $('#partnership-fields').slideUp();
                 }
             });

             $(document).on('click', '.btn-edit-jenis_kelas', function() {
                 $(".m-add-jenis_kelas").modal("show");
                 $("input[name='tipe_form']").val('edit');

                 $.ajax({
                     type: "POST",
                     url: '<?php echo site_url('MasterData/get_jenis_kelas'); ?>',
                     data: {
                         "id_jenis_kelas": $(this).attr('id'),
                     },
                     dataType: "json",
                     success: function(data) {
                         $("#label_tipe").text('Ubah');
                         $("input[name='id_jenis_kelas']").val(data[0].id_jenis_kelas);
                         $("input[name='nama_kelas']").val(data[0].nama_kelas);
                         $("select[name='gender']").val(data[0].gender);
                         $("select[name='usia']").val(data[0].usia);
                         $("select[name='tipe']").val(data[0].tipe);
                         $("input[name='biaya']").val(data[0].biaya);
                         $("input[name='biaya_regis']").val(data[0].biaya_regis);
                         $("input[name='biaya_buku']").val(data[0].biaya_buku);
                         $("input[name='nama_buku']").val(data[0].nama_buku);

                         // Partnership fields
                         if (data[0].is_partnership == '1') {
                             $('#is_partnership').prop('checked', true);
                             $('#partnership-fields').show();
                         } else {
                             $('#is_partnership').prop('checked', false);
                             $('#partnership-fields').hide();
                         }
                         $("input[name='nama_organisasi']").val(data[0].nama_organisasi || '');
                         $("input[name='kontak_partnership']").val(data[0].kontak_partnership || '');
                         $("textarea[name='alamat_partnership']").val(data[0].alamat_partnership || '');
                         $("input[name='no_telp_partnership']").val(data[0].no_telp_partnership || '');
                         $("input[name='jumlah_siswa_partnership']").val(data[0].jumlah_siswa_partnership || '');
                     },
                     error: function(request, status, error) {
                         console.log('Gagal ke Server')
                     }
                 });
             });



         });



         function hapus(id) {
             var id_jenis_kelas = id;
             Swal.fire({
                 title: 'Anda yakin hapus Jenis Kelas ini..?',
                 text: "",
                 icon: 'warning',
                 showCancelButton: true,
                 confirmButtonColor: '#3085d6',
                 cancelButtonColor: '#d33',
                 confirmButtonText: 'Ya',
                 cancelButtonText: "Tidak",
             }).then((result) => {
                 if (result.value) {
                     $.ajax({
                         type: "POST",
                         url: '<?php echo site_url('MasterData/hapus_jenis_kelas'); ?>',
                         data: {
                             id_jenis_kelas: id_jenis_kelas
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
                                 title: 'OK, Berhasil Dihapus'
                             });

                             setTimeout(function() {
                                 window.location.href =
                                     '<?php echo site_url('MasterData/jenis_kelas'); ?>';
                                 window.clearTimeout();
                             }, 1000);

                         },
                         error: function(request, status, error) {
                             console.log('Gagal ke Server')


                         }

                     });
                 }
                 if (result.dismiss == "cancel") {
                     console.log('batal');
                 }

             });

         }
     </script>