<?php

Class M_Member_Activity extends CI_Model{


 function selectTimesheet($id){
 	$query = $this->db->query("SELECT * FROM USER_TIMESHEET WHERE PROJECT_ID='".$id."'  ORDER BY ts_date DESC ");
     $hasil = $query->result_array();
     return $hasil;


 }
 
 
 function selectTimesheetAll(){
 	$query = $this->db->query("SELECT * FROM USER_TIMESHEET   ORDER BY ts_date DESC ");
     $hasil = $query->result_array();
     return $hasil;


 }


 function filter ($data,$id){
   $user_id =$data['USER_ID'];
$new_start =$data['START_DATE'];
$new_end	=$data['END_DATE'];

   	$query = $this->db->query("SELECT * FROM USER_TIMESHEET WHERE project_id='".$id."' AND user_id='".$user_id."' AND ts_date between to_date('$new_start','YYYY-MM-DD')
    and to_date('$new_end','YYYY-MM-DD' ) ORDER BY ts_date DESC  ");

 $hasil = $query->result_array();
 return $hasil;
 }
 
 function filterAll ($data){
   $user_id =$data['USER_ID'];
$new_start =$data['START_DATE'];
$new_end	=$data['END_DATE'];

   	$query = $this->db->query("SELECT * FROM USER_TIMESHEET WHERE user_id='".$user_id."' AND ts_date between to_date('$new_start','YYYY-MM-DD')
    and to_date('$new_end','YYYY-MM-DD' ) ORDER BY ts_date  ");

 $hasil = $query->result_array();
 return $hasil;
 }

}
