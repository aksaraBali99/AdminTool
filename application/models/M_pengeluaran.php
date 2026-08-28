<?php
class M_pengeluaran extends CI_Model
{

    var $column_order = ['tanggal', 'kategori', 'jumlah', 'keterangan']; // Kolom yang dapat diurutkan
    var $column_search = ['kategori', 'keterangan']; // Kolom yang bisa dicari
    var $order = ['id_pengeluaran' => 'desc']; // Urutkan berdasarkan id_pengeluaran

    public function __construct()
    {
        parent::__construct();
    }

    private function _get_datatables_query($bulan, $tahun)
    {
        $this->db->select('t.id_pengeluaran, t.tanggal, t.kategori, t.jumlah, t.keterangan');
        $this->db->from('pengeluaran t');

        if ($bulan) {
            $this->db->where('MONTH(t.tanggal)', $bulan);
        }

        if ($tahun) {
            $this->db->where('YEAR(t.tanggal)', $tahun);
        }


        // Pencarian
        if ($this->input->post('search')['value']) {
            $search_value = $this->input->post('search')['value'];
            $this->db->group_start();
            foreach ($this->column_search as $item) {
                $this->db->or_like($item, $search_value);
            }
            $this->db->group_end();
        }

        // Urutan
        if (isset($_POST['order'])) {
            $this->db->order_by($this->column_order[$_POST['order'][0]['column']], $_POST['order'][0]['dir']);
        } else if (isset($this->order)) {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    public function get_datatables($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        if ($_POST['length'] != -1) {
            $this->db->limit($_POST['length'], $_POST['start']);
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function count_filtered($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all($bulan, $tahun)
    {
        $this->db->from('tagihan t');
        return $this->db->count_all_results();
    }


    function get_pengeluaran($id_pengeluaran)
    {
        $query = "SELECT * FROM pengeluaran WHERE id_pengeluaran = '" . $id_pengeluaran . "'";
        $sql = $this->db->query($query);
        return $sql->result();
    }


    function update_pengeluaran($id, $data)
    {
        $this->db->where('id_pengeluaran', $id);
        $this->db->update('pengeluaran', $data);
    }
}
