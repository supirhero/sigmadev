<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Project extends CI_Controller
{
    public $datajson = array();

    function __construct()
    {
        parent::__construct();
        error_reporting(E_ALL & ~E_NOTICE);

        $this->load->model('M_project');
        $this->load->model('M_business');
        $this->load->model('M_session');
        $this->load->model('M_detail_project');
        $this->load->model('M_baseline');

        //TOKEN LOGIN CHECKER
        if(isset($_GET['token'])){
            $datauser["data"] = $this->M_session->GetDataUser($_GET['token']);

            $decoded_user_data =$datauser;
            //    print_r($decoded_user_data);
            $this->datajson['token'] = $_GET['token'];

        }
        elseif(isset($_SERVER['HTTP_TOKEN'])){
            $datauser["data"] = $this->M_session->GetDataUser($_SERVER['HTTP_TOKEN']);

            $decoded_user_data = $datauser["data"];
            $this->datajson['token'] = $_SERVER['HTTP_TOKEN'];
        }
        else{
            $error['error']="Login First!";
            echo json_encode($error);
            die();
        }
        //if login success
        if(!isset($decoded_user_data[0])){
            //get user data from token

            //for login bypass ,this algorithm is not used
            //$this->datajson['userdata'] = (array)$decoded_user_data['data'];
            //this code below for login bypass
            $this->datajson['userdata'] = $decoded_user_data;
        }
        //if login fail
        else {
            echo $decoded_user_data[0];
            die();
        }

        if($datauser["data"]["SESSION_EXPIRED"] <= time())
        {
            $error['error']="session is expired";
            echo json_encode($error);
            die();
        }
        else{
            $this->M_session->update_session($this->datajson['token']);
        }
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

        $data['type'] = $this->M_project->getProjectType();

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
        $data['type_of_effort'] =[[
            value=>1,
            name=>'CR'
        ],[
            value=>2,
            name=>'project'
        ],[
            value=>3,
            name=>'Manage Operation'
        ],[
            value=>4,
            name=>'Maintenance'
        ],[
            value=>7,
            name=>'Manage Service'
        ],[
            value=>8,
            name=>'Non Project'
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
    public function editProject_action(){
        $id=$_POST['PROJECT_ID'];
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
    /*End Edit Project*/

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
(SELECT CASE
    WHEN (
      sum(RESOURCE_WBS) > 0
      AND sum(RESOURCE_WBS) IS NOT NULL
    ) THEN
      sum(RESOURCE_WBS*duration*8)
    ELSE
      sum(duration*8)

      END as total from wbs  WHERE project_id='$project_id'
      GROUP BY project_id 
     )");
        $total_pv = $query->row()->TOTAL;
        $query = $this->db->query("
WITH date_range AS (
    SELECT  ACTUAL_START_DATE as start_date
           ,ACTUAL_END_DATE as end_date
    FROM    PROJECTS where project_id='$project_id'
    )

        
SELECT  t2.\"Week\",t2.\"startdate\",t2.\"enddate\",
            (select sum(t1.pv) ac from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as pv,
            (select sum(t1.ev) ev from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev

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

        echo json_encode($results);
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

        $array_data = json_decode($_POST['array'],true);

        //setting variable
        $user_id = $this->datajson['userdata']['USER_ID'];
        $project=$this->input->post("project_id");
        $rh_id = null;

        //setting for upload libary
        $config['upload_path']		= './document_assets/rebaseline_evidence/';
        $config['allowed_types']	= 'zip|doc|docs|docx|xls|pdf|xlsx';
        $config['max_size']			= 100000;
        $config['max_width']		= 1024;
        $config['max_height']		= 768;$this->load->library('upload', $config);

        /*for send verification email
        $project_name=$this->M_baseline->selectProjectName($project);
        $pm_name=$this->M_baseline->selectProjectPmName($project);
        $bu_name=$this->M_baseline->selectProjectBUName($project);
        $this->sendVerificationPMO($project_name,$project,$pm_name,$bu_name,$vp_bu);
        */
        //jika gagal upload/ tidak ada file
        if (! $this->upload->do_upload('evidence')){
            //get id rebaseline history
            $id = $this->M_baseline->getMaxBaselineID();
            $rh_id = $id;
            $data['RH_ID'] = $id;
            $data['PROJECT_ID'] = $this->input->post("project_id");
            $data['SUBMIT_DATE']= date('Y-m-d h:i:s');
            $data['REASON'] = $this->input->post("reason");
            $data['EVIDENCE'] = null;

            //insert rebaseline history
            $this->M_baseline->insertRebaseline($data);

            $data['message_evidence'] = 'no file or file failed uploaded';
            //edit table project
            //$this->M_baseline->editProject2($update,$id2);

        }
        // jika ada file evidence / berhasil upload
        else {
            $id = $this->M_baseline->getMaxBaselineID();
            $rh_id = $id;
            $data['RH_ID'] = $id;
            $data['PROJECT_ID'] = $this->input->post("project_id");
            $data['SUBMIT_DATE']= date('Y-m-d h:i:s');
            $data['REASON'] = $this->input->post("reason");
            $data['EVIDENCE'] = $this->upload->data()['file_name'];

            $this->M_baseline->insertRebaseline($data);

            $data['message_evidence'] = 'file success uploaded';
        }
        //set project status to onhold
        $this->db->query("Update projects set PROJECT_STATUS='On Hold',RH_ID = $rh_id where project_id='$project'");
        $datareturn['status_project'] = 'success';

        /*===========================================================*/
        //add modified task to temporary table
        if(count($array_data['modified_task']) != 0){
            foreach ($array_data['modified_task'] as $modtask){
                $this->M_detail_project->Edit_WBSTemp(
                    $modtask["wbs_id"],
                    $modtask["wbs_parent_id"],
                    $modtask["project_id"],
                    $modtask["wbs_name"],
                    $modtask['start_date'],
                    $modtask['finish_date'],
                    $rh_id
                );
            }
            $datareturn['status_edit_task'] = "success";
        }

        /*===========================================================*/
        //add new task to temporary table
        if(count($array_data['new_task']) != 0){
            foreach ($array_data['new_task'] as $newtask){
                $project_id   = $newtask['project_id'];

                //wbs id same with project id
                $data['RH_ID'] = $rh_id;
                $data['WBS_NAME'] = $newtask["wbs_name"];
                $data['WBS_ID'] = $project_id;
                $data['WBS_PARENT_ID'] = $newtask["wbs_parent_id"];
                $data['START_DATE']   = "TO_DATE('".$newtask['start_date']."','yyyy-mm-dd')";
                $data['FINISH_DATE']  ="TO_DATE('".$newtask["finish_date"]."','yyyy-mm-dd')";

                // insert into wbs temporary and get new ID
                $newid = $this->M_detail_project->insertWBSTemp($data,$project_id);

            }
            $datareturn['status_add_new_task'] = "success";
        }


        echo json_encode($datareturn);
    }

    public function accept_rebaseline(){

        $project_id = $this->input->post('project_id');
        //rebaseline history id
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;


        /*===================BATCH WBS=================*/
        $allTemporaryWbs = $this->db->query("select * from temporary_wbs where rh_id = '$rh_id' order by WBS_PARENT_ID")->result_array();
        if(count($allTemporaryWbs) != 0){
            foreach ($allTemporaryWbs as $wbsData){
                /*CREATE WBS*/
                if($wbsData['ACTION'] == 'create'){
                    $insertwbs = [
                        'WBS_ID'=>$wbsData['WBS_ID'],
                        'WBS_PARENT_ID'=>$wbsData['WBS_PARENT_ID'],
                        'PROJECT_ID'=>$wbsData['PROJECT_ID'],
                        'WBS_NAME'=>$wbsData['WBS_NAME'],
                        'START_DATE'=>$wbsData['START_DATE'],
                        'FINISH_DATE'=>$wbsData['FINISH_DATE'],
                        'DURATION'=>$wbsData['DURATION'],

                    ];
                    $this->db->insert('WBS',$insertwbs);

                    //update some value,idk what it will be ,but it come from old code , trust the old one boy..
                    $WP_ID= $this->M_detail_project->getMaxWPID();
                    $RP_ID= $this->M_detail_project->getMaxRPID();

                    //get all wbs data from new wbs
                    $selWBS=$this->M_detail_project->getWBSselected($wbsData['WBS_ID']);
                    $allParent = $this->M_detail_project->getAllParentWBS($selWBS->WBS_ID);

                    $dateStartWBS= new DateTime($selWBS->START_DATE);
                    $dateEndWBS= new DateTime($selWBS->FINISH_DATE);
                    foreach ($allParent as $ap) {
                        $dateStartParent=new DateTime($ap->START_DATE);
                        $dateEndParent=new DateTime($ap->FINISH_DATE);
                        if ($dateStartWBS<$dateStartParent) {
                            $this->M_detail_project->updateParentDate('start',$ap->WBS_ID,$dateStartWBS->format('Y-m-d'));
                        }
                        if ($dateEndWBS>$dateStartParent) {
                            $this->M_detail_project->updateParentDate('end',$ap->WBS_ID,$dateEndWBS->format('Y-m-d'));
                        }
                        $this->M_detail_project->updateNewDuration($ap->WBS_ID);
                    }

                }
                /*EDIT WBS*/
                if($wbsData['ACTION'] == 'update') {
                    $updatewbs = [
                        'WBS_ID' => $wbsData['WBS_ID'],
                        'WBS_PARENT_ID' => $wbsData['WBS_PARENT_ID'],
                        'PROJECT_ID' => $wbsData['PROJECT_ID'],
                        'WBS_NAME' => $wbsData['WBS_NAME'],
                        'START_DATE' => $wbsData['START_DATE'],
                        'FINISH_DATE' => $wbsData['FINISH_DATE'],
                        'DURATION' => $wbsData['DURATION'],

                    ];
                    $this->db->where('WBS_ID', $wbsData['WBS_ID']);
                    $this->db->update('WBS', $updatewbs);

                    //update some value,idk what it will be ,but it come from old code , trust the old one boy..
                    $selWBS = $this->getSelectedWBS($wbsData['WBS_ID']);
                    $allParent = $this->getAllParent($selWBS->WBS_ID);
                    $dateStartWBS = new DateTime($selWBS->START_DATE);
                    $dateEndWBS = new DateTime($selWBS->FINISH_DATE);
                    foreach ($allParent as $ap) {
                        $dateStartParent = new DateTime($ap->START_DATE);
                        $dateEndParent = new DateTime($ap->FINISH_DATE);
                        if ($dateStartWBS < $dateStartParent) {
                            $this->M_detail_project->updateParentDate('start', $ap->WBS_ID, $dateStartWBS->format('Y-m-d'));
                        }
                        if ($dateEndWBS > $dateStartParent) {
                            $this->M_detail_project->updateParentDate('end', $ap->WBS_ID, $dateEndWBS->format('Y-m-d'));
                        }
                        $this->M_detail_project->updateNewDuration($ap->WBS_ID);
                    }
                }
                /*DELETE WBS*/
                if($wbsData['ACTION'] == 'delete'){
                    //$this->M_detail_project->deleteWBSID($id);
                    //$this->M_detail_project->deleteWBSPoolID($id);
                    $this->M_detail_project->updateProgressDeleteTask($wbsData['WBS_ID']);
                }
            }
        }

        /*===================BATCH WBS POOL==============*/
        $allTemporaryWbsPool = $this->db->query("select * 
                                                 from temporary_wbs_pool a join wbs b
                                                 on a.wbs_id = b.wbs_id
                                                 where a.project_id = '$project_id'
                                                 and b.rh_id = '$rh_id'")->result_array();
        if(count($allTemporaryWbsPool) != 0){
            foreach ($allTemporaryWbsPool as $wbsPool){
                /*ADD MEMBER TO TASK*/
                if($wbsPool['ACTION'] == 'create'){
                    $wbs=$wbsPool['WBS_ID'];
                    $member=$wbsPool['RP_ID'];

                    $id = $wbsPool['WP_ID'];
                    $this->db->set('RP_ID', $member);
                    $this->db->set('WP_ID', $id);
                    $this->db->set('WBS_ID', $wbs);
                    $this->db->insert("WBS_POOL");

                    $res=$this->db->query("select count(rp_id) as RES from wbs_pool where wbs_id='$wbs'")->row()->RES;
                    $dur=$this->db->query("select DURATION as DUR from wbs where wbs_id='$wbs'")->row()->DUR;
                    $this->db->query("update wbs set resource_wbs=$res, WORK_COMPLETE=$dur*$res*8 where wbs_id='$wbs'");
                    $allParent=$this->getAllParentWBS($wbs);
                    foreach ($allParent as $ap) {
                        $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
                        $wc=0;
                        $allChild=$this->getAllChildWBS($ap->WBS_ID);
                        foreach ($allChild as $ac) {
                            $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
                            $wc=$wc+$works;
                        }
                        $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc' where wbs_id='$ap->WBS_ID'");
                    }
                }
                /*DELETE MEMBER FROM TASK*/
                if($wbsPool['ACTION'] == 'delete'){
                    $wbs=$wbsPool['WBS_ID'];
                    $member=$wbsPool['RP_ID'];

                    $this->db->where('RP_ID', $member);
                    $this->db->where('WBS_ID', $wbs);
                    $this->db->delete("WBS_POOL");

                    $res=$this->db->query("select count(rp_id) as RES from wbs_pool where wbs_id='$wbs'")->row()->RES;
                    $this->db->query("update wbs set resource_wbs=$res where wbs_id='$wbs'");
                    $allParent=$this->getAllParentWBS($wbs);
                    foreach ($allParent as $ap) {
                        $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
                        $wc=0;
                        $allChild=$this->getAllChildWBS($ap->WBS_ID);
                        foreach ($allChild as $ac) {
                            $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
                            $wc=$wc+$works;
                        }
                        $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc' where wbs_id='$ap->WBS_ID'");
                    }
                }
            }
        }

        /*===================BATCH TIMESHEET=============*/
        $allTemporayTimesheet = $this->db->query("select * from temporary_timesheet a join wbs_pool b
                                                  on a.wp_id =  b.wp_id
                                                  join wbs c
                                                  on b.wbs_id = c.wbs_id
                                                  where c.project_id = '$project_id'
                                                  and a.rh_id = '$rh_id'
                                                  ")->result_array();

        if(count($allTemporayTimesheet) != 0){
            foreach ($allTemporayTimesheet as $timesheet){

                //change date input for readable to sql
                $tgl=date_format(date_create($timesheet['TS_DATE']),'Ymd');

                //check timesheet data for this date ,
                //0 = no data
                //-1 = have an old data (Only one data)
                //1 = have a new data
                $jumlahts=$this->checkTSData($timesheet['WP_ID'],$tgl);

                //insert new data
                if($jumlahts == 0){
                    $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

                    //data for insert
                    $TS_ID = $timesheet['TS_ID'];
                    $SUBJECT = $timesheet['SUBJECT'];
                    $MESSAGE = $timesheet['MESSAGE'];
                    $HOUR_TOTAL = $timesheet['HOUR_TOTAL'];
                    $TS_DATE = "to_date('$tgl','yyyymmdd')";
                    $WP_ID = $timesheet['WP_ID'];
                    $LATITUDE = $timesheet['LATITUDE'];
                    $LONGITUDE = $timesheet['LONGITUDE'];
                    $SUMBIT_DATE =$timesheet['SUBMIT_DATE'];
                    $IS_APPROVED = $timesheet['IS_APPROVED'];
                    $APPROVAL_DATE = $timesheet['APPROVAL_DATE'];
                    $REJECTED_MESSAGE = $timesheet['REJECTED_MESSAGE'];
                    $CONFIRMED_BY = $timesheet['CONFIRMED_BY'];


                    $this->db->query("INSERT INTO TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE,SUBMIT_DATE,
                              IS_APPROVED,APPROVAL_DATE,REJECTED_MESSAGE,CONFIRMED_BY) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE','$SUMBIT_DATE',
                              $IS_APPROVED,'$APPROVAL_DATE','$REJECTED_MESSAGE','$CONFIRMED_BY')");

                    if($timesheet['IS_APPROVED'] == 1){
                        $this->M_timesheet->updateProgress($timesheet['TS_ID']);
                    }


                }
                //insert new data with add prefix number at primary key
                elseif($jumlahts == 1){
                    //get timesheet total this day
                    $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

                    //data for insert
                    $TS_ID = $data['WP_ID'].".$tgl.".str_pad(($getCountTimesheet+1),2,"0",STR_PAD_LEFT);
                    $SUBJECT = $data['SUBJECT'];
                    $MESSAGE = $data['MESSAGE'];
                    $HOUR_TOTAL = $data['WORK_HOUR'];
                    $TS_DATE = "to_date('$tgl','yyyymmdd')";
                    $WP_ID = $data['WP_ID'];
                    $LATITUDE = $data['LATITUDE'];
                    $LONGITUDE = $data['LONGITUDE'];

                    $this->db->query("INSERT INTO TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE')");


                }
                //change old primary key style first if data detected as old data
                elseif($jumlahts == -1){

                    //update query
                    $getOldData = $this->db->query("select * from timesheet where TS_DATE = to_date('$tgl','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array();
                    $this->db->set('TS_ID',$getOldData[0]['TS_ID'].".".str_pad(1,2,"0",STR_PAD_LEFT));
                    $this->db->where("TS_DATE = to_date('$tgl','yyyymmdd')");
                    $this->db->like('TS_ID', $data['WP_ID'].'.','after');

                    $queryupdate = "update TIMESHEET set TS_ID = '".$getOldData[0]['TS_ID'].".01' 
                              where TS_DATE = to_date('$tgl','yyyymmdd') 
                              and TS_ID LIKE '".$data['WP_ID'].".%'";
                    $this->db->query($queryupdate);


                    //insert query
                    $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

                    //data for insert
                    $TS_ID = $data['WP_ID'].".$tgl.".str_pad(($getCountTimesheet+1),2,"0",STR_PAD_LEFT);
                    $SUBJECT = $data['SUBJECT'];
                    $MESSAGE = $data['MESSAGE'];
                    $HOUR_TOTAL = $data['WORK_HOUR'];
                    $TS_DATE = "to_date('$tgl','yyyymmdd')";
                    $WP_ID = $data['WP_ID'];
                    $LATITUDE = $data['LATITUDE'];
                    $LONGITUDE = $data['LONGITUDE'];

                    $this->db->query("INSERT INTO TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE')");
                }
            }
        }

        $return['status'] = 'success';
        echo json_encode($return);
    }

    public function deny_rebaseline(){
        $project_id = $this->input->post('project_id');
        $this->db->query("update projects set project_status='In Progress',rh_id = null where project_id='$project_id'");
        if($this->db->affected_rows() == 1){
            $return['status'] = 'success';
        }
        else{
            $return['status'] = 'failed';
        }
        echo json_encode($return);

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