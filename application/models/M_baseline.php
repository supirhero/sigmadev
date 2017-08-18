<?php

Class M_baseline extends CI_Model{
    function selectProject($project_id){
        $query=$this->db->query("select * from PROJECTS where PROJECT_ID='".$project_id."'");
        $hasil = $query->result_array();
        return $hasil;

    }
    /*  function editMenu($data){
    $this->db->where('id', $id);
    $this->db->update('mytable', $data);
  }*/


    function getMaxBaselineID(){
        return $this->db->query("select nvl(max(rh_id)+1, 700001) as rh_id from REBASELINE_HISTORY")->row()->RH_ID;
    }

    function getMaxWBSID(){
        return $this->db->query("select nvl(max(wbs_id)+1, 700001) as wbs_id from WBS")->row()->WBS_ID;
    }

    function editProject($id,$update){

        $start2 =	$update['SCHEDULE_START'] ;
        $finish2 = $update['SCHEDULE_END'] ;
        $status = $update['PROJECT_STATUS'] ;

        $this->db->set('PROJECT_STATUS',$status['PROJECT_STATUS']);
        $this->db->set('SCHEDULE_START',"to_date('$start2','YYYY-MM-DD')",false);
        $this->db->set('SCHEDULE_END',"to_date('$finish2','YYYY-MM-DD')",false);
        $this->db->where('PROJECT_ID', $id);
        $this->db->update('PROJECTS');

    }

    function editProject2($update,$id2){

        $new_start =$update['SCHEDULE_START'];
        $new_end	=$update['SCHEDULE_END'];


        $this->db->set('SCHEDULE_START',"to_date('$new_start','YYYY-MM-DD')",false);
        $this->db->set('SCHEDULE_END',"to_date('$new_start','YYYY-MM-DD')",false);

        $this->db->where('PROJECT_ID', $id2);
        $this->db->update('PROJECTS');

    }

    public function insertRebaseline($data){
        $delivDate = $data['SUBMIT_DATE'];

        $this->db->set('RH_ID',$data['RH_ID']);
        $this->db->set('PROJECT_ID',$data['PROJECT_ID']);
        $this->db->set('REASON',$data['REASON']);
        $this->db->set('EVIDENCE',$data['EVIDENCE']);
        $this->db->set('SUBMIT_DATE',"to_timestamp('$delivDate','YYYY-MM-DD HH24:MI:SS')",false);
        //$data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");

        $this->db->insert("REBASELINE_HISTORY");
    }

    public function insertWBS($data){
        $id_wbs= $data['PROJECT_ID'].".".$this->db->query("select NVL(max(cast(WBS_ID as int))+1, 0) as NEW_ID from WBS_PROJECT where PROJECT_ID='".$data['PROJECT_ID']."' ")->row()->NEW_ID;
        $start =	$data['START_DATE'] ;
        $finish = $data['FINISH_DATE'] ;
        $actual_start =$data['ACTUAL_START_DATE'];
        $actual_end	=$data['ACTUAL_FINISH_DATE'];

        $this->db->set('WBS_ID',$id_wbs);
        $this->db->set('PROJECT_ID',$data['PROJECT_ID']);
        $this->db->set('WBS_NAME',$data['WBS_NAME']);
        $this->db->set('ACTUAL_START_DATE',"to_date('$actual_start','YYYY-MM-DD')",false);
        $this->db->set('ACTUAL_FINISH_DATE',"to_date('$actual_end','YYYY-MM-DD')",false);
        $this->db->set('START_DATE',"to_date('$start','YYYY-MM-DD')",false);
        $this->db->set('FINISH_DATE',"to_date('$finish','YYYY-MM-DD')",false);

        //$data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");

        $this->db->insert("WBS");

    }

    function selectProjectName($project){
        return $this->db->query("select a.project_id, a.project_name, b.user_name as PM, c.bu_name from projects a inner join users b on a.pm_id=b.user_id  inner join p_bu c on a.bu_code=c.bu_code where project_id ='".$project."'")->row()->PROJECT_NAME;

    }

    function selectProjectPmName($project){
        return $this->db->query("select a.project_id, a.project_name, b.user_name as PM, c.bu_name from projects a inner join users b on a.pm_id=b.user_id  inner join p_bu c on a.bu_code=c.bu_code where project_id ='".$project."'")->row()->PM;

    }

    function selectProjectBUName($project){
        return $this->db->query("select a.project_id, a.project_name, b.user_name as PM, c.bu_name from projects a inner join users b on a.pm_id=b.user_id  inner join p_bu c on a.bu_code=c.bu_code where project_id ='".$project."'")->row()->BU_NAME;

    }

    function ProjectName($project){
        return $this->db->query("select * from projects where project_id='".$project."'")->row()->PROJECT_NAME;

    }

    //email cc VP

    function selectBUid($id) {
        return $this->db->query("SELECT * from users where user_id='" . $id . "'")->row()->BU_ID;
    }

    function selectBUhead($p_bu) {
        return $this->db->query("select * from P_BU where bu_id='" . $p_bu . "'")->row()->BU_HEAD;
    }

    function selectVPBU($bu_head) {
        return $this->db->query("select * from users where user_id='" . $bu_head . "'")->row()->EMAIL;
    }

}



?>
