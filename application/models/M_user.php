<?php

class M_user extends CI_Model{	

	public function __construct(){ 
		
	} 
 	
    function cek_login($table,$where){		
            return $this->db->get_where($table,$where);
    }	
}