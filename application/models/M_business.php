<?php
Class M_business extends CI_Model
{
    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('M_report');
    }
    function getAllProfiling(){
        $result=array();
        $sql="select * from PROFILE";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result();
        }

        return $result;
    }

    function getAllMember(){
        $result=array();
        $sql="select * from USERS WHERE IS_ACTIVE ='1' ORDER BY USER_NAME ASC ";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result();
        }

        return $result;
    }

    function getProjectName($bu_code){
        $sql="select * from projects where bu_code = '".$bu_code."' ";
        $q=$this->db->query($sql);
        $result=$q->result();
        return $result;
    }

    function get_project_name($bu_code)
    {
        $q = ("select * from projects where bu_code ='".$bu_code."' ORDER BY PROJECT_NAME ASC");
        return $this->db->query($q)->result();
    }

    function getProjectStatus(){
        $sql="select distinct PROJECT_STATUS from projects order by PROJECT_STATUS asc";
        $q=$this->db->query($sql);
        $result=$q->result();
        return $result;
    }

    function get_project_type(){
        $sql="select distinct PROJECT_TYPE from p_project_category";
        $q=$this->db->query($sql);
        $result=$q->result();
        return $result;
    }

    function get_project_year($bu_id){
        $q = ("
select distinct tahun from tb_project_bu where bu_id='".$bu_id."' order by tahun desc");
        return $this->db->query($q)->result();
    }

    function getBuCode($bu_id){
        $sql="select bu_code from p_bu where bu_id='".$bu_id."'";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->BU_CODE;
            return $result;
        }
    }

    function ChangeMemberBU($user_id,$bu_id){
        $sql="UPDATE USERS SET BU_ID='".$bu_id."' WHERE USER_ID='".$user_id."'";
        $q = $this->db->query($sql);
    }

    function getEmail($user_id){
        $sql="select EMAIL from USERS where USER_ID='".$user_id."'";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->EMAIL;
            return $result;
        }
    }

    function getBUName($bu_id){
        $sql="select BU_NAME from P_BU where BU_ID='".$bu_id."'";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->BU_NAME;
            return $result;
        }
    }

    public function project_spi_cpi ()
    {


        $data['tampil_dashboard']=($this->M_report->dashboard_all());

        if($_SESSION['userdata']['logged_in'])
        {
            if ($this->session->userdata('PROF_ID')!=8){
                $this->load->view('v_detail_bu',$data);
            }
            else {
                redirect('Report/report_dru');
            }

        }else{
            redirect('Login');
        }


    }

    function getWUProfilingAccess($userid=null){
        $result=array();
        if(isset($userid)){
            $sql="SELECT C.USER_ID AS USER_ID, C.USER_NAME AS USER_NAME, C.BU_ID AS BU_ID, C.USER_TYPE_ID AS USER_TYPE_ID, B.PROF_ID AS PROF_ID, B.PROF_NAME AS PROF_NAME, C.EMAIL AS EMAIL FROM USERS C INNER JOIN PROFILE B ON C.PROF_ID=B.PROF_ID where C.USER_ID='".$userid."'";
        }else{
            $sql="SELECT C.USER_ID AS USER_ID, C.USER_NAME AS USER_NAME, C.BU_ID AS BU_ID, C.USER_TYPE_ID AS USER_TYPE_ID, B.PROF_ID AS PROF_ID, B.PROF_NAME AS PROF_NAME, C.EMAIL AS EMAIL FROM USERS C INNER JOIN PROFILE B ON C.PROF_ID=B.PROF_ID";
        }

        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result();
        }

        return $result;
    }

    function updateRoleBusiness($user_id,$profile){
        $sql="update USERS set PROF_ID='".$profile."' where USER_ID='".$user_id."' ";
        $this->db->query($sql);
    }

    function getMaxBU(){
        return $this->db->query("select nvl(max(bu_id)+1, 7000001) as NEW_ID from P_BU")->row()->NEW_ID;
    }

    function addNewBU(){
        //not finished
        $bu_id = $this->M_business->getMaxBU();
        $res_par_id;
        $res_par_level;
        $bu_parent_id=$this->input->post('BU_PARENT_ID');
        $sql="select * from p_bu where BU_ID='".$this->input->post('BU_PARENT_ID')."'";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $res_par_id = $q->row();
        }

        $bu=array(
            'BU_ID'=>$bu_id,
            'BU_ALIAS'=>strtoupper($this->input->post('BU_ALIAS')),
            'BU_CODE'=>strtoupper($this->input->post('BU_CODE')),
            'BU_NAME'=>ucwords($this->input->post('BU_NAME')),
            'BU_PARENT_ID'=>$bu_parent_id,
            'BU_PARENT_CODE'=>$res_par_id->BU_CODE,
            'IS_ACTIVE'=>'1',
            'BU_HEAD'=>$this->input->post('BU_HEAD')
        );
        $this->db->insert('P_BU',$bu);
    }
    function getUserList(){
        $result=array();
        $sql="select USER_ID,USER_NAME from USERS where USER_TYPE_ID='int' and (PROF_ID='3' or PROF_ID='2')
ORDER BY USER_NAME asc";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result();
        }

        return $result;
    }
    function buListResArr(){
        $result=array();
        $sql="select p_bu.*, LEVEL from p_bu  connect by  bu_parent_id = prior bu_id
start with bu_id=0
order siblings by bu_parent_id";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result_array();
        }

        return $result;
    }
    function buListResArrLimit(){
        $result=array();
        $sesBU=$this->session->userdata('BU_ID');
        $sql="  Select *
  From p_bu where IS_ACTIVE=1
  Start With bu_id = '".$sesBU."'
  Connect By bu_id = Prior bu_parent_id
Union
  Select *
  From p_bu
  Where bu_parent_id ='".$sesBU."' and IS_ACTIVE=1";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result_array();
        }

        return $result;
    }
    function buList(){
        $result=array();
        $sql="select p_bu.*, LEVEL from p_bu  connect by  bu_parent_id = prior bu_id
start with bu_id=0
order siblings by bu_parent_id";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result();
        }

        return $result;
    }
    function getBUParent($id){
        $result=array();
        $sql="select * from p_bu where bu_id='".$id."'";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result = $q->row()->BU_CODE;
        }

        return $result;
    }
    function getBUParentStatus($id){
        $result=array();
        $sql="select * from p_bu where bu_id='".$id."' and IS_ACTIVE=1";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result = $q->row()->IS_ACTIVE;
        }

        return $result;
    }
    function checkExist($params,$val){
        $result='';
        $sql="select * from P_BU where ".$params."='".$val."'";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $check=TRUE;
        }else{
            $check=FALSE;
        }
        switch($params){
            case 'BU_CODE':
                if($check==TRUE){
                    $result='code_dup';
                }else{
                    $result='code_no_dup';
                }
                break;
            case 'BU_ALIAS':
                if($check==TRUE){
                    $result='alias_dup';
                }else{
                    $result='alias_no_dup';
                }
                break;
        }
        return $result;
    }
    function getData($bu_id){
        $result=array();
        $sql="select * from p_bu where bu_id='".$bu_id."'";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result = $q->row();
        }

        return $result;
    }

    function getAllBU(){
        $result=array();
        $sql="select * from P_BU WHERE IS_ACTIVE ='1'";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result_array();
        }

        return $result;
    }


    function updateBUData(){
        $code=$this->input->post('BU_CODE');
        $id=$this->input->post('BU_ID');
        $name=$this->input->post('BU_NAME');
        $alias=$this->input->post('BU_ALIAS');
        $head=$this->input->post('BU_HEAD');
        $sql="update p_bu set BU_CODE='".$code."', BU_ALIAS='".$alias."', BU_NAME='".$name."', BU_HEAD='".$head."' where BU_ID='".$id."'";
        $q = $this->db->query($sql);
    }
    function updateBULevel(){
        $bu_id=$this->input->post('BU_ID');
        $bu_parent_id=$this->input->post('BU_PARENT_ID');
        $sql="update p_bu set BU_PARENT_ID='".$bu_parent_id."' where BU_ID='".$bu_id."'";
        $q = $this->db->query($sql);
    }
    function updateBUStatus(){

        $stat=$this->input->post('STAT');
        $bu_id=$this->input->post('BU_ID');
        if($stat=='0'){
            $sql="select p_bu.*, LEVEL from p_bu connect by  bu_parent_id = prior bu_id
start with BU_ID='".$bu_id."'
order siblings by bu_parent_id";
            $q3 = $this->db->query($sql);
            if($q3->num_rows() > 0){
                $result = $q3->result_array();
            }
            foreach($result as $res){
                $sql2="update p_bu set IS_ACTIVE='0' where BU_ID='".$res['BU_ID']."'";
                $q2 = $this->db->query($sql2);
            }
        }else{
            $sql="update p_bu set IS_ACTIVE='".$stat."' where BU_ID='".$bu_id."'";
            $q = $this->db->query($sql);
        }
    }
    function getBUTest(){
        $sql="SELECT * FROM P_BU";
        $q=$this->db->query($sql);
        $result=$q->result();
        return $result;
    }

    function getDataByBuCode($bu_code){
        $findbu = $this->db->query("select bu_id from p_bu where bu_code = '$bu_code'")->row();

        $bu_id = $findbu->BU_ID;

        $result=array();
        $sql="select * from p_bu where bu_id='".$bu_id."'";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result = $q->row();
        }

        return $result;
    }
}
?>
