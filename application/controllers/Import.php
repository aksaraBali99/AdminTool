<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Import extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('m_masterdata');
		$this->load->model('m_user');
		$this->load->library('excel');

		if ($this->session->userdata('is_login') !== true) {
			redirect(site_url("Login"));
		}
	}



	public function import_excel_siswa()
	{
		if (isset($_FILES["fileExcel"]["name"])) {
			$path = $_FILES["fileExcel"]["tmp_name"];
			$object = PHPExcel_IOFactory::load($path);
			
			// Target sheet "Data Siswa"
			$worksheet = null;
			foreach ($object->getWorksheetIterator() as $sheet) {
				if (strpos(strtolower($sheet->getTitle()), 'siswa') !== false) {
					$worksheet = $sheet;
					break;
				}
			}

			if (!$worksheet) {
				$worksheet = $object->getActiveSheet();
			}

			// Deteksi Header untuk pemetaan kolom otomatis
			$highestColumn = $worksheet->getHighestColumn();
			$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
			$colMap = [
				'nama_anak' => 1, // Default B
				'jk' => 2, // Default C
				'tgl_lahir_anak' => 4, // Default E
				'usia' => 5, // Default F
				'nama_sekolah' => 9, // Default J
				'nama_ortu' => 10, // Default K
				'alamat' => 11, // Default L
				'email' => 12, // Default M
				'src' => 13, // Default N
				'status_siswa' => 14, // Default O
				'level_siswa' => 16 // Default Q
			];

			// Coba cari header di baris 1
			for ($col = 0; $col < $highestColumnIndex; $col++) {
				$headerText = strtolower(trim($worksheet->getCellByColumnAndRow($col, 1)->getValue()));
				if ($headerText == 'nama') $colMap['nama_anak'] = $col;
				else if (strpos($headerText, 'jk') !== false || strpos($headerText, 'kelamin') !== false) $colMap['jk'] = $col;
				else if (strpos($headerText, 'tgl lahir') !== false || strpos($headerText, 'tanggal lahir') !== false) $colMap['tgl_lahir_anak'] = $col;
				else if (strpos($headerText, 'usia') !== false || strpos($headerText, 'umur') !== false) $colMap['usia'] = $col;
				else if (strpos($headerText, 'sekolah') !== false) $colMap['nama_sekolah'] = $col;
				else if (strpos($headerText, 'orang tua') !== false) $colMap['nama_ortu'] = $col;
				else if (strpos($headerText, 'alamat') !== false) $colMap['alamat'] = $col;
				else if (strpos($headerText, 'email') !== false) $colMap['email'] = $col;
				else if (strpos($headerText, 'sumber') !== false) $colMap['src'] = $col;
				else if (strpos($headerText, 'status') !== false) $colMap['status_siswa'] = $col;
				else if (strpos($headerText, 'level') !== false) $colMap['level_siswa'] = $col;
			}

			// Load jenis kelas & levels to map
			$jenis_kelas = $this->db->get('data_jenis_kelas')->result();
			$map_kelas = [];
			foreach ($jenis_kelas as $jk) {
				$map_kelas[strtolower(trim($jk->nama_kelas))] = $jk->id_jenis_kelas;
			}

			$mst_level = $this->db->get('mst_level_siswa')->result();
			$map_level = [];
			foreach ($mst_level as $ml) {
				$map_level[strtolower(trim($ml->nama_level))] = $ml->id_level;
			}

			$this->db->trans_start();

			$highestRow = $worksheet->getHighestRow();
			for ($row = 2; $row <= $highestRow; $row++) {
				$nama_anak = $worksheet->getCellByColumnAndRow($colMap['nama_anak'], $row)->getValue();
				
				// Validasi: Abaikan jika nama kosong atau berisi kode buku (HC1, HC2, dst)
				if (empty($nama_anak) || preg_match('/^HC\d+$/i', trim($nama_anak))) {
					continue;
				}

				$tgl_lahir_raw = $worksheet->getCellByColumnAndRow($colMap['tgl_lahir_anak'], $row)->getValue();
				$tgl_lahir = null;

				if (!empty($tgl_lahir_raw)) {
					// Jika PHPExcel membaca sebagai format tanggal numeric
					if (is_numeric($tgl_lahir_raw)) {
						$tgl_lahir = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($tgl_lahir_raw));
					} else {
						// Jika terbaca sebagai string, coba parsing
						$tgl_lahir = date('Y-m-d', strtotime($tgl_lahir_raw));
					}
				}

				// Fallback ke hitungan usia jika Tgl Lahir masih kosong
				if (empty($tgl_lahir) || $tgl_lahir == '1970-01-01') {
					$usia_raw = $worksheet->getCellByColumnAndRow($colMap['usia'], $row)->getValue();
					$usia = preg_replace('/[^0-9]/', '', $usia_raw);
					if (!empty($usia)) {
						$year_birth = date('Y') - intval($usia);
						$tgl_lahir = $year_birth . '-01-01';
					}
				}

				$jk_raw = $worksheet->getCellByColumnAndRow($colMap['jk'], $row)->getValue();
				$jk = (strtolower(substr(trim($jk_raw), 0, 1)) == 'p') ? 'P' : 'L';

				$level_excel_raw = $worksheet->getCellByColumnAndRow($colMap['level_siswa'], $row)->getValue();
				$level_excel = strtolower(trim($level_excel_raw));
				
				$id_jenis_kelas = isset($map_kelas[$level_excel]) ? $map_kelas[$level_excel] : null;
				$id_level = isset($map_level[$level_excel]) ? $map_level[$level_excel] : null;

				$data_peserta = array(
					'nama_anak'      => $nama_anak,
					'tgl_lahir_anak' => $tgl_lahir,
					'jk'             => $jk,
					'nama_sekolah'   => $worksheet->getCellByColumnAndRow($colMap['nama_sekolah'], $row)->getValue(),
					'nama_ortu'      => $worksheet->getCellByColumnAndRow($colMap['nama_ortu'], $row)->getValue(),
					'alamat_ortu'    => $worksheet->getCellByColumnAndRow($colMap['alamat'], $row)->getValue(),
					'alamat_anak'    => $worksheet->getCellByColumnAndRow($colMap['alamat'], $row)->getValue(), 
					'email'          => $worksheet->getCellByColumnAndRow($colMap['email'], $row)->getValue(),
					'src'            => $worksheet->getCellByColumnAndRow($colMap['src'], $row)->getValue(),
					'status_siswa'   => $worksheet->getCellByColumnAndRow($colMap['status_siswa'], $row)->getValue() ?: 'Aktif',
					'id_jenis_kelas' => $id_jenis_kelas,
					'status'         => 'Registrasi Kelas',
					'jenis_siswa'    => 'regular',
					'input_at'       => date('Y-m-d H:i:s'),
					'is_aktif'       => 1
				);

				$this->db->insert('peserta', $data_peserta);
				$id_peserta = $this->db->insert_id();

				if ($id_level) {
					$this->db->insert('riwayat_level_siswa', [
						'id_siswa' => $id_peserta,
						'id_level' => $id_level,
						'tanggal_kenaikan_level' => date('Y-m-d'),
						'is_aktif' => 1,
						'catatan' => 'Import dari Excel'
					]);
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$script = "<script>alert('Data Gagal diimport');window.location.href = '" . site_url('Peserta/peserta') . "';</script>";
				echo $script;
			} else {
				$script = "<script>alert('Data Berhasil diimport');window.location.href = '" . site_url('Peserta/peserta') . "';</script>";
				echo $script;
			}
		} else {
			$script = "<script>alert('Pilih file terlebih dahulu');window.location.href = '" . site_url('Peserta/peserta') . "';</script>";
			echo $script;
		}
	}
}
