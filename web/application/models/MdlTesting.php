<?php
class MdlTesting extends CI_Model{
    public function __construct()
    {
        //ngeload database
        $this->load->database();
    }
    function tes(){
        return $this->db->query('describe USERS');
    }
}