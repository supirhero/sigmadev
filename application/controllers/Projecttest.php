<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Projecttest extends CI_Controller
{
    public $datajson = array();
    function __construct()
    {
        parent::__construct();
        $this->load->model('M_project');
        $this->load->model('M_business');
        $this->datajson['userdata'] = json_decode("{
    \"USER_ID\": \"S201502162\",
    \"USER_NAME\": \"GINA KHAYATUNNUFUS\",
    \"EMAIL\": \"gina.nufus@sigma.co.id\",
    \"BU_ID\": \"36\",
    \"USER_TYPE_ID\": \"int\",
    \"SUP_ID\": \"S201404159\",
    \"PROF_ID\": \"6\",
    \"last_login\": \"17-JUL-17\",
    \"logged_in\": true
  }", true);

    }


    function index(){

    }

    /*START ADD PROJECT*/
    public function addProject_view(){



        $code = $this->M_project->getBuBasedCode($_POST['bu_code']);
        //get bussines unit based on uri segment
        $data['business_unit'] = $this->M_business->getDataByBuCode($_POST['bu_code']);

        //get pm
        $data['project_manager'] = $this->M_project->getPMBuCode($_POST['bu_code']);

        echo json_encode($data);
    }
    //Check if iwo already used
    public function checkiwoused(){
        $iwo=$this->input->post('IWO_NO');
        $returndata['jumlah'] = $this->M_project->verifyIWO($iwo);
        echo json_encode($returndata);
    }
    //checking iwo when IWO selected at "Create Project" page
    public function check() {
        $IWO = $this->input->post("IWO_NO");
        //$IWO="P-1608SCC-TSEL0561";
        $res = $this->M_project->checkIWO($IWO);
        print_r($res);
    }
    //checking Account Manager from iwo number
    public function checkAM() {
        $am = $this->input->post("AM_ID");
        // $bu='14';
        //  $am='S200804071';
        // $json = file_get_contents('http://10.210.20.2/api/index.php/mis/customer/' . $cust_id);
        $data=$this->M_project->getAM($am);
        //  echo $data;
        //$data=$this->M_project->getCustomer($cust_id);
        //$data = json_decode($json, true);
        $returndata['username'] = $data[0]['USER_NAME'];
        echo json_encode($returndata);
    }
    //checking Customer from iwo number
    public function checkCustomer() {
        $cust_id = $this->input->post("CUST_ID");
        //$cust_id=41000004;
        $json = file_get_contents('http://180.250.18.227/api/index.php/mis/customer/' . $cust_id);
        //$data=$this->M_project->getCustomer($cust_id);
        $data = json_decode($json, true);
        $returndata['customer_name'] = $data[0]['CUSTOMER_NAME'];
        echo json_encode($returndata);
    }
    //checking project type id
    public function checkProjectType(){
        $data = $this->input->post('PROJECT_TYPE_ID');
        $res['type_of_effort'] = $this->M_project->getProjectCat($data);
        echo json_encode($res);
    }
    //add project if verified
    public function addProject_acion(){
        $test=$this->M_project->addProject($this->datajson['userdata']);
        $SCHEDULE_START = $this->input->post('START');
        $SCHEDULE_END = $this->input->post('END');
        $dur=$this->countDurationAll($SCHEDULE_START,$SCHEDULE_END);
        $this->M_project->addProjectWBS($test,$dur);

        $returndata['status']='success';
        $returndata['message'] = 'Project success added';
        echo json_encode($returndata);
    }
    private function countDurationAll($start_date, $end_date) {
        if (empty($start_date)) {
            $start_date=date('Y-m-d');
        }
        if (empty($end_date)) {
            $end_date=date('Y-m-d');
        }
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = $end->diff($start);
        $days = $interval->days;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        return $days;
    }
    /*END ADD PROJECT*/

    /*Start Edit Project*/
    public function editProject_view(){

        $data['project_setting']=$this->M_project->getProject($this->uri->segment(3));
        $bu_id=$this->M_project->getProjectID($this->uri->segment(3));
        $data['project_business_unit_detail'] = $this->M_business->getData($bu_id);
        //$data['pm'] = $this->M_project->getPM($bu_id);
        $data['available_project_type'] = $this->M_project->getProjectType();
        $code = $this->M_project->getBUCodeByProjectID($this->uri->segment(3));
        //$data['pm'] = $this->M_project->getPM($this->uri->segment(3));

        /*=================================================================*/
        //get Project Manager account manager
        $q="SELECT USER_NAME, USER_ID FROM USERS WHERE BU_ID='".$bu_id."' AND IS_ACTIVE='1' order by USER_NAME";
        $amq="SELECT USER_NAME, USER_ID FROM USERS WHERE IS_ACTIVE='1' order by USER_NAME";

        $bu =$this->uri->segment(3);
        $pm=$this->db->query($q)->result_array();
        $am=$this->db->query($amq)->result_array();

        $data['project_manajer_list'] = $pm;
        $data['account_manager_list'] = $am;

        $data['type_of_expense'] = ['Capital Expense','Current Expense','Dedctible Expense'];
        $data['type_of_effort'] =[
          [
            'value'=>1,
            'name'=>'CR'
        ],[
            'value'=>2,
            'name'=>'project'
        ],[
            'value'=>3,
            'name'=>'Manage Operation'
        ],[
            'value'=>4,
            'name'=>'Maintenance'
        ],[
            'value'=>7,
            'name'=>'Manage Service'
        ],[
            'value'=>8,
            'name'=>'Non Project'
        ]];
        $data['project_status'] = ['Not Started','In Progress','On Hold','Completed','Cancelled'];
        $data['project_type'] = [];
        $project_type = $this->db->query('select project_type from p_project_type')->result_array();
        foreach ($project_type as $type){
            array_push($data['project_type'],$type['PROJECT_TYPE']);
        }


        $this->transformKeys($data);
        echo json_encode($data);
    }
    function editProject_action(){
        $id=$this->uri->segment(3);
        $confirm = $this->M_project->update($id);
        if($confirm){
            $returndata['status']='success';
            $returndata['message'] = 'success edit project';
        }
        else{
            $returndata['status']='error';
            $returndata['message'] = 'error edit project';
        }
        echo json_encode($returndata);
    }
    function gantt($project_id)
    {
        $list=$this->M_project->getWBS($project_id);

        /// end here
        foreach($list as $l){
            $wbs[]=array('text'=>$l['TEXT'],'id'=>$l['ID'],'parent'=>$l['PARENT'],'start_date'=>date("Y-m-d",strtotime($l['START_DATE'])),'duration'=>$l['DURATION'],'progress'=>$l['PROGRESS']);
        }
        echo json_encode($wbs);
    }
    function report($project_id)
    {
        $report = [
            "week1" => [
                "start_date" =>"01-05-2017",
                "end_date" =>"05-05-2017",
                "ev" =>"102",
                "pev" =>"5",
                "pv" =>"110",
                "ppv" =>"5",
                "ac" =>"110",
                "spi" =>"0.9",
                "cpi" =>""
            ],
            "week2" => [
                "start_date" =>"08-05-2017",
                "end_date" =>"12-05-2017",
                "ev" =>"202",
                "pev" =>"10",
                "pv" =>"230",
                "ppv" =>"11",
                "ac" =>"200",
                "spi" =>"1.4",
                "cpi" =>"1.1",
            ],
            "week3" => [
                "start_date" =>"15-05-2017",
                "end_date" =>"19-05-2017",
                "ev" =>"202",
                "pev" =>"10",
                "pv" =>"230",
                "ppv" =>"11",
                "ac" =>"200",
                "spi" =>"1.4",
                "cpi" =>"1.1",
            ],
        ];
        echo json_encode($report);
    }
    function spi($project_id)
    {
        $query = $this->db->query("
WITH date_range AS (
    SELECT  ACTUAL_START_DATE as start_date
           ,ACTUAL_END_DATE as end_date
    FROM    PROJECTS where project_id='$project_id'
    )
SELECT  t2.\"Week\",t2.\"startdate\",t2.\"enddate\",
            (select max(t1.pv)-min(t1.pv) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as pv,
            (select max(t1.ev)-min(t1.ev) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev,
            (select (max(t1.ev)-min(t1.ev))/nullif(max(t1.pv)-min(t1.pv), 0) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as spi

            FROM   (SELECT  LEVEL \"Week\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') \"startdate\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') + 6 \"enddate\"
       ,TO_CHAR(start_date + (7 * (LEVEL - 1)),'IW') \"Iso Week\"
FROM   date_range t2
CONNECT BY LEVEL <= (TRUNC(end_date,'IW') - TRUNC(start_date,'IW')) / 7 + 1) t2
");
        $result["spi"] = $query->result();
        echo json_encode($result);
    }
    function cpi($project_id)
    {
        $query = $this->db->query("WITH date_range AS (
    SELECT  ACTUAL_START_DATE as start_date
           ,ACTUAL_END_DATE as end_date
    FROM    PROJECTS where project_id='$project_id'
    )
SELECT  t2.\"Week\",t2.\"startdate\",t2.\"enddate\",
            (select max(t1.ac)-min(t1.ac) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev,
            (select max(t1.ev)-min(t1.ev) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev,
            (select (max(t1.ev)-min(t1.ev))/nullif(max(t1.ac)-min(t1.ac), 0) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as spi

            FROM   (SELECT  LEVEL \"Week\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') \"startdate\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') + 6 \"enddate\"
       ,TO_CHAR(start_date + (7 * (LEVEL - 1)),'IW') \"Iso Week\"
FROM   date_range t2
CONNECT BY LEVEL <= (TRUNC(end_date,'IW') - TRUNC(start_date,'IW')) / 7 + 1) t2");
        $result["cpi"] = $query->result();
        echo json_encode($result);
    }
    function s_curve($project_id)
    {
        $query = $this->db->query("
SELECT sum(CASE
    WHEN (
      RESOURCE_WBS > 0
      AND RESOURCE_WBS IS NOT NULL
    ) THEN
    RESOURCE_WBS
    ELSE
    1 
    END*4*duration) as total from wbs WHERE project_id=$project_id");
        $total_pv = $query->row()->TOTAL;

        $query = $this->db->query("
WITH date_range AS (
    SELECT  ACTUAL_START_DATE as start_date
           ,ACTUAL_END_DATE as end_date
    FROM    PROJECTS where project_id='$project_id'
    )

        
SELECT  t2.\"Week\",t2.\"startdate\",t2.\"enddate\",
            (select max(t1.pv) ac from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as pv,
            (select max(t1.ev) ev from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev

            FROM   (SELECT  LEVEL \"Week\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') \"startdate\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') + 4 \"enddate\"
       ,TO_CHAR(start_date + (7 * (LEVEL - 1)),'IW') \"Iso Week\"
FROM   date_range t2
CONNECT BY LEVEL <= (TRUNC(end_date,'IW') - TRUNC(start_date,'IW')) / 7 + 1) t2
");
        $result = $query->result();
        $results = [];
        foreach ($result as $key => $val)
        {
            foreach ($val as $week => $valz)
            {
                $results[$key][$week]= $valz;
            }
            $results[$key]["pv_percent"]=round($val->PV/$total_pv*100);
            $results[$key]["ev_percent"]=round($val->EV/$total_pv*100);
        }
        $resultz["s-curve"]=$results;
        print_r($resultz);
    }

    /*Baseline*/
    function baseline(){
        $project=$this->uri->segment(3);

        $this->db->query("Update projects set PROJECT_STATUS='In Progress' where project_id='$project'");
        $data['status'] = "success";
        echo json_encode($data);
    }

    //rebaseline
    public function rebaseline() {
        $config['upload_path']		= './assets/image/';
        $config['allowed_types']	= 'zip|doc|docs|docx|xls|pdf|xlsx';
        $config['max_size']			= 100000;
        $config['max_width']		= 1024;
        $config['max_height']		= 768;
        //$config['file_name']		= $nm;
        $this->load->library('upload', $config);
        //$project='141';

        $user_id = $this->datajson['userdata']['USER_ID'];
        $p_bu=$this->M_baseline->selectBUid($user_id);
        $bu_head=$this->M_baseline->selectBUhead($p_bu);
        $vp_bu=$this->M_baseline->selectVPBU($bu_head);

        // echo $vp_bu;
        $project=$this->uri->segment(3);
        $project_name=$this->M_baseline->selectProjectName($project);
        $pm_name=$this->M_baseline->selectProjectPmName($project);
        $bu_name=$this->M_baseline->selectProjectBUName($project);



        //$this->sendVerificationPMO($project_name,$project,$pm_name,$bu_name,$vp_bu);
        $this->db->query("Update projects set PROJECT_STATUS='On Hold' where project_id='$project'");
        //jika gagal upload/ tidak ada file
        if (! $this->upload->do_upload('fileup')){
            $id = $this->M_baseline->getMaxBaselineID();
            $data['RH_ID'] = $id;
            $data['PROJECT_ID'] = $this->input->post("PROJECT_ID");
            $data['REASON'] = $this->input->post("REASON");
            $data['EVIDENCE'] = $this->input->post("EVIDENCE");
            $data['OLD_START_DATE'] = $this->input->post("SCHEDULE_START");
            $data['OLD_END_DATE'] = $this->input->post("SCHEDULE_END");
            $data['NEW_START_DATE'] = $this->input->post("NEW_START_DATE");
            $data['NEW_END_DATE'] = $this->input->post("NEW_END_DATE");
            $data['SUBMIT_DATE'] 			= $this->input->post("SUBMIT_DATE");

            $id2 =$this->uri->segment(3);
            $data2['PROJECT_ID'] =$id2;
            $update['SCHEDULE_START'] = $this->input->post("NEW_START_DATE");
            $update['SCHEDULE_END'] = $this->input->post("NEW_END_DATE");

            $this->M_baseline->insertRebaseline($data);
            $this->M_baseline->editProject2($update,$id2);




            //  print_r($data);

        }
        else {
            $id = $this->M_baseline->getMaxBaselineID();
            $data['RH_ID'] = $id;
            $data['PROJECT_ID'] = $this->input->post("PROJECT_ID");
            $data['REASON'] = $this->input->post("REASON");
            $data['fileup']			= $this->upload->data('fileup');
            $data['OLD_START_DATE'] = $this->input->post("SCHEDULE_START");
            $data['OLD_END_DATE'] = $this->input->post("SCHEDULE_END");
            $data['NEW_START_DATE'] = $this->input->post("NEW_START_DATE");
            $data['NEW_END_DATE'] = $this->input->post("NEW_END_DATE");
            $data['SUBMIT_DATE'] 			= $this->input->post("SUBMIT_DATE");

            $id2 =$this->uri->segment(3);
            $data2['PROJECT_ID'] =$id2;
            $update['SCHEDULE_START'] = $this->input->post("NEW_START_DATE");
            $update['SCHEDULE_END'] = $this->input->post("NEW_END_DATE");

            $this->M_baseline->insertRebaseline($data);
            $this->M_baseline->editProject2($update,$id2);

        }
        $project=$this->uri->segment(3);
        $this->db->query("Update projects set PROJECT_STATUS='On Hold' where project_id='$project'");
        $data['status'] = 'success';

        echo json_encode($data);
    }

    private function transformKeys(&$array)
    {
        foreach (array_keys($array) as $key):

            # Working with references here to avoid copying the value,
            # since you said your data is quite large.
            $value = &$array[$key];
            unset($array[$key]);
            # This is what you actually want to do with your keys:
            #  - remove exclamation marks at the front
            #  - camelCase to snake_case
            $transformedKey = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', ltrim($key, '!')));
            # Work recursively
            if (is_array($value)) $this->transformKeys($value);
            # Store with new key
            $array[$transformedKey] = $value;
            # Do not forget to unset references!
            unset($value);
        endforeach;
    }


}