<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Siswa extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		logged_in();

	}

	public function index()
	{
		$data = array(
			'title' => 'Siswa',
		);

		$this->include->layout('index_siswa', $data);

	}

	public function list_siswa()
	{
		$this->load->model('SiswaModel', 'siswa');
		$bulider = $this->siswa->getDataTable();
		$data 	 = array();
		$start 	 = $this->input->post('start');
		$no  	 = $start > 0 ? $start + 1 : 1;
		foreach ($bulider['dataTable'] as $field) {
			$start++;
			$row 	= array();

			$nis 		= $field->nis ? $field->nis : '-'; 
			$nama_siswa = $field->nama_siswa ? $field->nama_siswa : '-';

			if ($field->jenis_kelamin == 'L') {
				$jenis_kelamin = 'Laki-laki';
			} elseif ($field->jenis_kelamin == 'P') {
				$jenis_kelamin = 'Perempuan';
			} else {
				$jenis_kelamin = '-';
			}

			$nama_kelas 	= $field->nama_kelas ? $field->nama_kelas : '-';
			$no_handphone 	= $field->no_handphone ? $field->no_handphone : '-';

            $aksi = '<div class="btn-group btn-group-sm">';
            $aksi .= '<a href="'. base_url('edit/siswa/'. base64_encode($field->id_siswa)) .'" class="btn btn-success"><i class="fas fa-edit"></i></a>';
            $aksi .= '<button type="button" class="btn btn-danger" onclick="delete_data('. "'". base64_encode($field->id_siswa) ."'" .')"><i class="fas fa-trash"></i></button>';
            $aksi .= '</div>';
			
			$row[]  = '<div style="text-align: center;">'. $no++ .'</div>';
			$row[]  = '<div style="text-align: left;">'. $nis .'</div>';
			$row[]  = '<div style="text-align: left;">'. $nama_siswa .'</div>';
			$row[]  = '<div style="text-align: left;">'. $jenis_kelamin .'</div>';
			$row[]  = '<div style="text-align: left;">'. $nama_kelas .'</div>';
			$row[]  = '<div style="text-align: left;">'. $no_handphone .'</div>';
			$row[]  = '<div style="text-align: center;">'. $aksi .'</div>';

			$data[]	= $row;
		}

		$output = array(
			'draw' 				=> $this->input->post('draw'),
			'recordsTotal'		=> $bulider['recordsTotal'],
			'recordsFiltered' 	=> $bulider['recordsFiltered'],
			'data' 				=> $data,
		);

		echo json_encode($output);
	}

	public function add_edit_siswa($id_siswa = null)
	{
		$query = $this->db->get_where('siswa', ['id_siswa' => base64_decode($id_siswa)])->row();

		$judul = isset($query->id_siswa) ? 'Ubah Siswa' : 'Tambah Siswa';

		$data = array(
			'title' 	=> 'Siswa',
			'header'	=> $judul,
			'kelas'		=> $this->db->get('kelas')->result(),
			'row'		=> $query,
		);

		$this->include->layout('add_edit_siswa', $data);
	}

	public function save_siswa()
	{
		$query = $this->db->get_where('siswa', ['id_siswa' => $this->input->post('id_siswa')])->row();

		if (isset($query->id_siswa)) {

			# Validasi Ubah Siswa

			$unique_nis =  $query->nis != $this->input->post('nis') ? '|is_unique[siswa.nis]' : '';


			$unique_no_handphone =  $query->no_handphone != $this->input->post('no_handphone') ? '|is_unique[siswa.no_handphone]' : '';

		} else {


			$unique_nis = '|is_unique[siswa.nis]';
			$unique_no_handphone = '|is_unique[siswa.no_handphone]';

		}

		$this->load->library('form_validation');

		$list_fields = array(
			'nis' 			=> [
				'NIS' => 'trim|required|numeric'. $unique_nis
			],
			'nama_siswa' 	=> ['Nama Lengkap' => 'trim|required'],
			'jenis_kelamin' => ['Jenis Kelamin' => 'trim|required'],
			'kelas_id' 		=> ['Kelas' => 'trim|required'],
			'no_handphone' 	=> ['No. Handphone' => 'trim|numeric'. $unique_no_handphone],
		);

		$this->form_validation->set_error_delimiters('', '');
		foreach ($list_fields as $key1 => $value1) {
			foreach ($value1 as $label => $rules) {
				$this->form_validation->set_rules($key1, $label, $rules);
			}
		}

		$this->form_validation->set_message('required', '{field} harus diisi.');
		$this->form_validation->set_message('numeric', '{field} hanya boleh berisi angka.');
		$this->form_validation->set_message('is_unique', '{field} sudah terdaftar.');

		if ($this->form_validation->run() == FALSE) {

			$list_errors = array();
			foreach ($list_fields as $key => $value) {
				$list_errors[$key] = form_error($key);
			}

			$output = array(
				'status'	=> false,
				'errors'	=> $list_errors,
			);

		} else {

			$field_names = array_keys($list_fields);
			$data = array();
			for ($i=0; $i < count($field_names); $i++) { 
				$data[$field_names[$i]] = $this->input->post($field_names[$i]) ? $this->input->post($field_names[$i]) : null;
			}

			if (count($data) >= 1) {
				if (isset($query->id_siswa)) {
					$this->db->update('siswa', $data, ['id_siswa' => $query->id_siswa]);
					$this->session->set_flashdata('success', 'Mengubah Siswa Berhasil!');
				} else {
					$this->db->insert('siswa', $data);
					$this->session->set_flashdata('success', 'Menambah Siswa Berhasil!');
				}
			}

			$output = array('status' => true);
		}

		echo json_encode($output);
	}

	public function delete_siswa($id_siswa = null)
	{
		$query = $this->db->get_where('siswa', ['id_siswa' => base64_decode($id_siswa)])->row();
		if (empty($query->id_siswa)) {
			show_404();
		}
		$this->db->delete('siswa', ['id_siswa' => $query->id_siswa]);
		echo json_encode([
			'status'	=> true,
			'message'	=> 'Menghapus Siswa Berhasil!'
		]);
	}
}

/* End of file Siswa.php */
/* Location: ./application/controllers/Siswa.php */
