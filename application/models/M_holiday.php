<?php

Class M_holiday extends CI_Model{
    function selectHoliday($keyword=null){
      $sql="select * from P_HOLIDAY";
      if ($keyword!=null) {
        $keyword=strtolower($keyword);
        $sql.=" where ";
        $sql.=" lower(HOLIDAY) like '%".$keyword."%' or";
        $sql.=" lower(HOLIDAY_START) like '%".$keyword."%' or";
        $sql.=" lower(HOLIDAY_END) like '%".$keyword."%' ";
      }
        $query = $this->db->query($sql);
        $hasil = $query->result_array();
        return $hasil;

    }
    /*  function editMenu($data){
    $this->db->where('id', $id);
    $this->db->update('mytable', $data);
  }*/


    function getMaxHoliday(){
        return $this->db->query("select nvl(max(holiday_id)+1, 700001) as NEW_ID from p_holiday")->row()->NEW_ID;
    }

    public function insertHoliday($data){
        //$this->db->insert("P_HOLIDAY", $data);
        $sql="INSERT INTO P_HOLIDAY (HOLIDAY_ID,HOLIDAY,HOLIDAY_START,HOLIDAY_END, COLOR) VALUES (
  '".$data['HOLIDAY_ID']."',
  '".$data['HOLIDAY']."',
  ".$data['HOLIDAY_START'].",
  ".$data['HOLIDAY_END'].",
  '".$data['COLOR']."')";
        $q=$this->db->query($sql);
        $res=$this->db->query("select * from P_HOLIDAY where HOLIDAY_ID='".$data['HOLIDAY_ID']."'");
        if ($res->num_rows()>0) {
          return $res->row_array();
        }else{
          return false;
        }
    }

    function lastLogin($data){
        $delivDate = date('d-m-Y');
        $sql="UPDATE P_HOLIDAY SET HOLIDAY_DATE=to_date('".$delivDate."','dd-mm-yy') WHERE HOLIDAY_ID='".$user_id."'";
        $q = $this->db->query($sql);
    }

    public function deleteHoliday($id){
        $this->db->delete('P_HOLIDAY', array('HOLIDAY_ID' => $id));
    }

    function editHoliday($data){
        $sql="UPDATE P_HOLIDAY SET HOLIDAY='".$data['HOLIDAY']."', HOLIDAY_START=to_date('".$data['HOLIDAY_START']."','yyyy-mm-dd'),HOLIDAY_END=to_date('".$data['HOLIDAY_END']."','yyyy-mm-dd') WHERE HOLIDAY_ID='".$data['HOLIDAY_ID']."'";
        $q = $this->db->query($sql);
        $res=$this->db->query("select * from P_HOLIDAY where HOLIDAY_ID='".$data['HOLIDAY_ID']."'");
        if ($res->num_rows()>0) {
          return $res->row_array();
        }else{
          return false;
        }
    }

    function selectCalendar(){
        $sql="select HOLIDAY_ID, HOLIDAY, TO_CHAR(HOLIDAY_START,'yyyy-mm-dd') AS HOLIDAY_START,TO_CHAR(HOLIDAY_END,'yyyy-mm-dd') AS HOLIDAY_END, COLOR FROM P_HOLIDAY where HOLIDAY_START is not null";
        $query = $this->db->query($sql);
        $hasil = $query->result_array();
        return $hasil;
    }

    public function deleteCalendar($id){
        $this->db->delete('P_HOLIDAY', array('HOLIDAY_ID' => $id));
    }

    public function view_row(){
        return $this->db->get('P_HOLIDAY')->result();
    }
}



?>
