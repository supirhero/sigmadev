<?php
Class M_invite extends CI_Model
{
	function __construct() {
        parent::__construct();
        $this->load->database();
    }

	function getAllBUName(){
 	$result=array();
 	$sql="select * from P_BU";
 	$q = $this->db->query($sql); 
    
    if($q->num_rows() > 0){
    	$result = $q->result();
    	}
    	
    return $result;
 }
 
 	function getMemberOfBU($bu_id){
	$result=array();
 	$sql="select * from USERS where BU_ID='".$bu_id."' ";
 	$q = $this->db->query($sql); 
    
    if($q->num_rows() > 0){
    	$result = $q->result();
    	}
    	
    return $result;	
	}
	
	function insertDataMember($data){
		
		for($x=0; $x<$data; $x++ ){
		$this->db->insert("RESOURCE_POOL", $data);	
		}
		
	}

}