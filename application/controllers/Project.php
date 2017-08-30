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

            $decoded_user_data =$datauser['data'];
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
        //newest
        /*FOR PRIVILEGE*/
        /*===============================================================================*/
        //PRIVILEGE CHECKER
        /*
        $url_dest = strtolower($this->uri->segment(1)."/".$this->uri->segment(2));
        $privilege = $this->db->query("select al.access_id,al.type,au.access_url,pal.privilege
                                    from access_list al
                                    join access_url au
                                    on al.access_id = au.access_id
                                    join profile_access_list pal
                                    on
                                    pal.access_id = au.access_id
                                    where pal.profile_id = ".$this->datajson['userdata']['PROF_ID']."
                                    order by al.type asc
                                    ")->result_array();
        $profile_id = $this->datajson['userdata']['PROF_ID'];
        foreach($privilege as $priv){
            //bypass privilege if user is prouds admin
            if($profile_id != 7){
                //jika akses url ada di dalam db
                if($priv['ACCESS_URL'] == $url_dest){
                    //jika akses tipe nya business
                    if($priv['TYPE'] == 'BUSINESS'){
                        if($priv['PRIVILEGE'] == 'all_bu'){

                        }
                        elseif($priv['PRIVILEGE'] == 'only_bu'){
                            //fetching busines unit
                            $user_bu = $this->datajson['userdata']['BU_ID'];
                            $user_bu_parent = $this->db->query("select bu_parent_id from p_bu where bu_id = '$user_bu'")->row()->BU_PARENT_ID;

                            $directorat_bu = [];
                            //if company
                            if($user_bu == 0){
                                $access = 'masuk';
                            }
                            //if directorat
                            elseif ($user_bu_parent == 0){
                                $bu_id_all= $this->db->query("select bu_id from p_bu where bu_parent_id = '$user_bu'")->result_array();
                                foreach ($bu_id_all as $buid){
                                    $directorat_bu[] = $buid['BU_ID'];
                                }
                            }
                            //if bu
                            else{
                                $directorat_bu[]  = $this->datajson['userdata']['BU_ID'];
                            }

                            switch ($priv['ACCESS_ID']){
                                //Update Personal Timesheet
                                case '1':
                                    $bu_id = $this->db->query(" select p_bu.bu_id 
                                                            from (select wp_id,wbs_id from wbs_pool
                                                            union 
                                                            select wp_id,wbs_id from temporary_wbs_pool) wbs_pool
                                                            join (select wbs_id,project_id from wbs union select wbs_id,project_id from temporary_wbs) wbs
                                                            on wbs_pool.wbs_id = wbs.wbs_id 
                                                            join projects
                                                            on wbs.project_id = projects.project_id
                                                            join p_bu
                                                            on projects.bu_code = p_bu.bu_code
                                                            where wbs_pool.wp_id = '".$_POST['WP_ID']."'
                                                            ")->row()->BU_ID;

                                    break;
                                //Access Business Unit Overview
                                case '2':
                                    //get bu id from bu code
                                    $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
                                    $this->datajson['userdata']['BU_ID'] = $bu_id;
                                    break;
                                //Create Project
                                case '3':
                                    $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['BU']."'")->row()->BU_ID;
                                    break;
                                //Access All Project In Business Unit
                                case '4' :
                                    if($url_dest == 'project/projectmember_add'){
                                        $projectid = $_POST['project_id'];
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$projectid'")->row()->BU_ID;
                                    }
                                    elseif ($url_dest == 'project/projectmember_delete'){
                                        $id = $_POST['MEMBER'];
                                        $project_id = $this->M_detail_project->getRPProject($id);
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$project_id'")->row()->BU_ID;
                                    }
                                    elseif($url_dest == 'project/editproject_action'){
                                        $id=$_POST['PROJECT_ID'];
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$id'")->row()->BU_ID;
                                    }
                                    elseif ($url_dest == 'project/gantt' || $url_dest == 'project/spi' || $url_dest == 'project/cpi' || $url_dest == 'project/s_curve' || $url_dest == 'baseline'){
                                        $projectid = $this->uri->segment(3);
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$projectid'")->row()->BU_ID;
                                    }
                                    elseif ($url_dest == 'project/rebaseline' || $url_dest == 'project/accept_rebaseline' || $url_dest == 'project/deny_rebaseline'){
                                        $id = $this->input->post("project_id");
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$id'")->row()->BU_ID;
                                    }
                                    elseif ($url_dest == 'task/createtask'){
                                        $project_id   = $this->input->post("PROJECT_ID");
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$project_id'")->row()->BU_ID;
                                    }
                                    elseif ($url_dest == 'task/edittask_action'){
                                        $project_id   = $this->input->post("project_id");
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$project_id'")->row()->BU_ID;
                                    }
                                    elseif ($url_dest == 'task/deletetask'){
                                        $id = $_POST['wbs_id'];
                                        $project_id = $this->M_detail_project->getProjectTask($id);
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$project_id'")->row()->BU_ID;
                                    }
                                    elseif ($url_dest == 'task/assigntaskmemberproject' || $url_dest == 'removetaskmemberproject'){
                                        $project_id = explode(".",$_POST['WBS_ID']);
                                        $project_id = $project_id[0];
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$project_id'")->row()->BU_ID;
                                    }
                                    elseif ($url_dest == 'task/upload_wbs'){
                                        $project_id = $this->input->post('project_id');
                                        $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$project_id'")->row()->BU_ID;
                                    }

                                    break;
                                //Approve Timesheet(Non-project) search in this case
                                case '5' :
                                    $bu_id = $this->db->query("select p_bu.bu_id from 
                                                            (select ts_id,wp_id from timesheet union select ts_id,wp_id from temporary_timesheet) timesheet
                                                            JOIN 
                                                            (select wp_id,wbs_id from wbs_pool union select wp_id,wbs_id from temporary_wbs_pool) wbs_pool
                                                            on timesheet.wp_id = wbs_pool.wp_id
                                                            JOIN 
                                                            (select project_id,wbs_id from wbs union select project_id,wbs_id from temporary_wbs) wbs
                                                            on wbs_pool.wbs_id = wbs.wbs_id
                                                            JOIN projects
                                                            on wbs.project_id = projects.project_id
                                                            JOIN p_bu 
                                                            on projects.bu_code = p_bu.bu_code
                                                            where timesheet.ts_id = '".$_POST['ts_id']."'
                                                            and projects.project_type_id = 'Non Project'
                                                            ")->row()->BU_ID;
                                    break;
                                //See Report Overview
                                case '6' :
                                    //LIMIT REPORT OVERVIEW BY ADD IN CLAUSE TO QUERY
                                    break;
                                //See Resource Report
                                case '7':
                                    $bu_id = $_POST['BU_ID'];
                                    break;
                                //Download Report
                                case '8':

                                    break;
                                //Approve or deny rebaseline (search in this case)
                                case '9':
                                    $projectid = $_POST['project_id'];
                                    $bu_id = $this->db->query("select bu_id from projects where project_id = '$projectid'")->row()->BU_ID;
                                    break;
                            }
                            if(array_search($bu_id,$directorat_bu) || $bu_id == 'masuk'){
                                $this->allowedBU = $directorat_bu;
                            }
                            else{
                                $this->output->set_status_header(403);
                                $returndata['status'] = 'failed';
                                $returndata['message'] = 'Anda tidak bisa mengakses feature yang ada di business unit ini';
                                echo json_encode($returndata);
                                die;
                            }
                        }
                        else{
                            $this->output->set_status_header(403);
                            $returndata['status'] = 'failed';
                            $returndata['message'] = 'Anda tidak bisa mengakses feature yang ada di business unit ini';
                            echo json_encode($returndata);
                            die;
                        }

                    }
                    //jika akses tipe nya project
                    elseif($priv['TYPE'] == 'PROJECT'){
                        //fetching granted project list
                        $granted_project = $this->db->query("SELECT   distinct project_id
                                                           FROM (SELECT a.user_id, a.user_name, c.project_id, c.project_name, c.bu_code, z.bu_name,
                                                                        c.project_complete, c.project_status, c.project_desc,
                                                                        c.created_by
                                                                   FROM USERS a INNER JOIN resource_pool b ON a.user_id = b.user_id
                                                                        INNER JOIN projects c ON b.project_id = c.project_id
                                                                        INNER JOIN p_bu z on c.bu_code = z.bu_code
                                                                 UNION
                                                                 SELECT a.user_id, a.user_name, b.project_id, b.project_name, b.bu_code, z.bu_name,
                                                                        b.project_complete, b.project_status, b.project_desc,
                                                                        b.created_by
                                                                   FROM USERS a INNER JOIN projects b ON a.user_id = b.created_by
                                                                   INNER JOIN p_bu z on b.bu_code = z.bu_code
                                                                        )
                                                                        where user_id='" . $this->datajson['userdata']['USER_ID'] . "' or created_by='" . $this->datajson['userdata']['USER_ID'] . "'")->result_array();
                        $granted_project_list = [];
                        //rearrange project list so it can readable to array search
                        foreach ($granted_project as $gp){
                            $granted_project_list[] = $gp['PROJECT_ID'];
                        }

                        if($priv['PRIVILEGE'] == 'can'){
                            //get project id
                            switch ($priv['ACCESS_ID']){
                                //Upload, create, edit, and delete workplan
                                case '10':
                                    switch ($url_dest){
                                        case 'task/createtask':
                                            $project_id_req = $_POST['PROJECT_ID'];
                                            break;
                                        case 'task/upload_wbs':
                                            $project_id_req = $_POST['PROJECT_ID'];
                                            break;
                                        case 'task/deletetask':
                                            $id = $_POST['wbs_id'];
                                            $project_id_req = $this->M_detail_project->getProjectTask($id);

                                            break;
                                    }
                                    break;
                                //Assign Task
                                case '11':
                                    $project_id_req = explode(".",$_POST['WBS_ID']);
                                    $project_id_req = $project_id_req[0];
                                    break;
                                //Baseline - Rebaseline project
                                case '12':
                                    $project_id_req = $_POST['project_id'];
                                    break;
                                //Update progress manually
                                case '13':
                                    $project_id_req = $_POST['PROJECT_ID'];
                                    break;
                                //Approve timesheet
                                case '14':
                                    $project_id_req = $_POST['project_id'];
                                    break;
                                //Edit Project
                                case '15':
                                    $project_id_req = $_POST['PROJECT_ID'];
                                    break;
                                //See Project Report
                                case '16':
                                    $project_id_req = $this->uri->segment(3);
                                    break;
                                //Download Report
                                case '17':
                                    break;
                            }
                        }
                        else{
                            $this->output->set_status_header(403);
                            $returndata['status'] = 'denied';
                            $returndata['message'] = 'you dont have permission to access this action';
                            echo json_encode($returndata);
                            die;
                        }
                        if(!in_array($project_id_req,$granted_project_list)){
                            $this->output->set_status_header(403);
                            $returndata['status'] = 'denied';
                            $returndata['message'] = 'you dont have permission to access this action';
                            echo json_encode($returndata);
                            die;
                        }
                    }
                    else{
                        $this->output->set_status_header(403);
                        $returndata['status'] = 'denied';
                        $returndata['message'] = 'you dont have permission to access this action';
                        echo json_encode($returndata);
                        die;
                    }
                }
            }
        }
        /*===============================================================================*/

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
    //function for count days
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

    //Add project member view
    public function ProjectMember_view(){
        $id = $this->uri->segment(3);
        $data['project_member']=$this->M_detail_project->getDataProject($id);

        echo json_encode($data);
    }
    //get project member
    public function ProjectMember_get(){
        $id =$this->input->post('BU_CODE');
        $project_id =$this->input->post('PROJECT_ID');


        //load available project member from external
        $query = $this->db->query("
            SELECT DISTINCT USERS.USER_ID, USERS.BU_ID, USER_TYPE_ID, IS_ACTIVE,USER_NAME,users.EMAIL
            FROM USERS LEFT JOIN RESOURCE_POOL ON USERS.USER_ID=RESOURCE_POOL.USER_ID
            WHERE USER_TYPE_ID='ext' AND IS_ACTIVE='1' AND NOT EXISTS
            (SELECT USER_ID FROM RESOURCE_POOL WHERE USERS.USER_ID=RESOURCE_POOL.USER_ID AND PROJECT_ID='".$project_id."')");
        $hasil = $query->result_array();
        $data['project_member_ext'] = $hasil;

        //load available project member from internal
        $bu_id  =   $this->M_detail_project->getBU($id);
        $query = $this->db->query("
            SELECT DISTINCT USERS.USER_ID, USERS.BU_ID, USER_TYPE_ID, IS_ACTIVE,USER_NAME,users.EMAIL
            FROM USERS LEFT JOIN RESOURCE_POOL ON USERS.USER_ID=RESOURCE_POOL.USER_ID
            WHERE USERS.BU_ID='".$bu_id."' AND USER_TYPE_ID='int' AND IS_ACTIVE='1' AND NOT EXISTS
            (SELECT USER_ID FROM RESOURCE_POOL WHERE USERS.USER_ID=RESOURCE_POOL.USER_ID AND PROJECT_ID='".$project_id."')
            ORDER BY USER_NAME ASC");
        $hasil = $query->result_array();
        $data['project_member_int'] = $hasil;

        //load availale project member related
        $bu_code_related = $this->db->query("select related_bu from projects where project_id = '$project_id'")->row()->RELATED_BU;
        $bu_code_related = explode(',',$bu_code_related);
        $bu_id_related = [];
        foreach($bu_code_related as $related){
            $bu_id_related[] = $this->db->query("select bu_id from p_bu where bu_code = '$related'")->row()->BU_ID;
        }
        $teks_bu_related = "";
        for($iter =0 ; $iter<count($bu_id_related) ; $iter++){

            $teks_bu_related = $teks_bu_related."'".$bu_id_related[$iter]."'";
            if($iter != count($bu_id_related)-1){
                $teks_bu_related = $teks_bu_related.",";
            }
        }


        $query = $this->db->query("
            SELECT DISTINCT USERS.USER_ID, USERS.BU_ID, USER_TYPE_ID, IS_ACTIVE,USER_NAME,users.EMAIL
            FROM USERS LEFT JOIN RESOURCE_POOL ON USERS.USER_ID=RESOURCE_POOL.USER_ID
            WHERE USERS.BU_ID IN ($teks_bu_related) AND USER_TYPE_ID='int' AND IS_ACTIVE='1' AND NOT EXISTS
            (SELECT USER_ID FROM RESOURCE_POOL WHERE USERS.USER_ID=RESOURCE_POOL.USER_ID AND PROJECT_ID='".$project_id."')
            ORDER BY USER_NAME ASC");
        $hasil = $query->result_array();

        $data['project_member_related'] = $hasil;
        echo json_encode($data);

    }
    //action add project member
    public function ProjectMember_add(){
        $id       = $this->M_detail_project->getMaxNumberResource();
        $project_id   = $this->input->post('PROJECT_ID');
        //  $email  = $this->input->post('EMAIL');
        //  $USER_ID    = $this->input->post("USER_ID");
        $user_id 	= $this->input->post('USER_ID');
        $ya       = count($_POST['USER_ID']);
        //$send     = count($USER_ID);
        $email				= $this->M_detail_project->selectemail($user_id[0]);
        $project_name = $this->M_detail_project->selectProjectName($project_id);

        for($i=0; $i<$ya; $i++){
            $data = $_POST['USER_ID'][$i];
            $email=$this->M_detail_project->selectemail($data);
            $query = "insert into RESOURCE_POOL (RP_ID,USER_ID,PROJECT_ID ) values ((select nvl(max(RP_ID)+1,1) from RESOURCE_POOL),'".$data."','".$project_id."')";
            $this->db->query($query);
        }
        //print_r ($email);
        $this->sendVerificationinviteMember($email,$project_name,$project_id);
        echo $this->email()->print_debugger();
    }
    //created by fa3af
    //for god sake, please inform me on +62 81230012673 if u dare to change this
    public function projectmemberadd($project){
      // prepare data from post
      $user=$_POST['USER_ID'];
      // check jika user sudah ada di dalam project atau tidak
      $check=$this->M_project->checkifinproject($project,$user);
      if ($check) {
        //jika true, maka tampilkan pesan error, menandakan user sudah pernah diinvite ke dalam project
        $c['status']="Error";
        $c['msg']="User sudah ada di dalam project";
      }else{
        //jika false, tambahkan user ke dalam resource_pool
        $this->M_project->addprojectmember($project,$user);
        $c['status']="Success";
        $c['msg']="User berhasil diinvite ke dalam project";
        //kirim email ke user bersangkutan
        $email				= $this->M_detail_project->selectemail($user);
        $project_name = $this->M_detail_project->selectProjectName($project);
        $this->sendVerificationinviteMember($email,$project_name,$project);
      }
      echo json_encode($c);
    }
    //action delete project member
    public function ProjectMember_delete(){
        $id = $_POST['MEMBER'];
        //echo $id;

        $project_id = $this->M_detail_project->getRPProject($id);
        //echo $project_id;
        $this->M_detail_project->deleteRPmember($id);

    }


    /*Start Edit Project*/
    public function editProject_view(){

        $data['project_setting']=$this->M_project->getProject($this->uri->segment(3));
        if(isset($_POST['mobile'])){
            array_walk_recursive($data['project_setting'], function(&$item, $key) {
                if ($item == null ){
                    $item = 'null';
                }
            });
        }
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

        $usediwo = $this->db->query("select distinct iwo_no from projects")->result_array();

        //get iwo
        @$json = file_get_contents('http://180.250.18.227/api/index.php/mis/iwo/');
        $IWO = array();
        $IWO = json_decode($json, true);

        $result_iwo1 = [];
        $result_iwo2 = [];



        foreach ($usediwo as $ui){
            $result_iwo2[] = $ui['IWO_NO'];
        }

        foreach($IWO as $iwo){
            $result_iwo1[] = $iwo['IWO_NO'];
        }



        $result_iwo = array_diff($result_iwo1,$result_iwo2);
        foreach ($result_iwo as $key => $val)
        {
            $hasil['iwo'][]= $IWO[$key];
        }
        //echo json_encode($result_iwo);

        $data["iwolist"]=$hasil['iwo'];

        $this->transformKeys($data);

        echo json_encode($data);
    }
    public function editProject_action(){

        if(isset($_POST['mobile'])){
            $_POST = array_change_key_case($_POST,CASE_UPPER);
        }

        $id=$_POST['PROJECT_ID'];
        $confirm = $this->M_project->update($id);
        if($confirm){
            $returndata['status']='success';
            $returndata['message'] = 'success edit project';
        }
        else{
            $this->output->set_status_header(400);
            $returndata['status']='error';
            $returndata['message'] = 'error edit project';

        }
        echo json_encode($returndata);
    }
    /*End Edit Project*/


    /*REPORT PROJECT*/
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
            (select max(t1.ac)-min(t1.ac) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ac,
            (select max(t1.ev)-min(t1.ev) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev,
            (select (max(t1.ev)-min(t1.ev))/nullif(max(t1.ac)-min(t1.ac), 0) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as cpi

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
        $resultz["s_curve"]=$results;
        print_r(json_encode($resultz));
    }
    /*END REPORT PROJECT*/


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
                $newid = $this->M_detail_project->insertWBSTemp($data,$project_id,$rh_id);

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
                                                 where b.project_id = '$project_id'
                                                 and a.rh_id = '$rh_id'")->result_array();
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
                    $getCountTimesheet = $this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array()[0]['TS_ID'];

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
                    $getCountTimesheet = $this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array()[0]['TS_ID'];

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
                    $getCountTimesheet = $this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array()[0]['TS_ID'];

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
    private function getSelectedWBS($id){
        return $this->M_detail_project->getWBSselected($id);
    }
    private function getAllParent($id){
        return $this->M_detail_project->getAllParentWBS($id);
    }

    private function getAllParentWBS($id){
        return $this->db->query("SELECT CONNECT_BY_ISLEAF AS LEAF, WBS.*, LEVEL
            FROM WBS where WBS_ID NOT IN ('".$id."') CONNECT BY  WBS_ID=PRIOR WBS_PARENT_ID
            START WITH WBS_ID='".$id."' ORDER SIBLINGS BY WBS_PARENT_ID")->result();
    }

    private function sendVerificationinviteMember($email,$project_name,$project_id){

        //$email='dakan@sigma.co.id';
        $this->load->library('email');
        $config['protocol']='smtp';
        $config['smtp_host']='smtp.sigma.co.id';
        $config['smtp_user']=SMTP_AUTH_USR;
        $config['smtp_pass']=SMTP_AUTH_PWD;
        $config['smtp_port']='587';
        $config['smtp_timeout']='100';
        $config['charset']    = 'utf-8';
        $config['newline']    = "\r\n";
        $config['mailtype'] = 'html';
        $config['validation'] = TRUE;
        $this->email->initialize($config);
        $this->email->from('prouds.support@sigma.co.id', 'Project & Resources Development System');
        $this->email->to($email);
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject('Invite Member to Project');
        $this->email->message("<!DOCTYPE html>
  <html>
  <head>
  <meta name='viewport' content='width=device-width' />
  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
  <title>Remove Member</title>

  <style>
  /* -------------------------------------
  GLOBAL
  ------------------------------------- */
  * {
    margin:0;
    padding:0;
  }
  * { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; }

  img {
    max-width: 100%;
  }
  .collapse {
    margin:0;
    padding:0;
  }
  body {
    -webkit-font-smoothing:antialiased;
    -webkit-text-size-adjust:none;
    width: 100%!important;
    height: 100%;
  }


  /* -------------------------------------
  ELEMENTS
  ------------------------------------- */
  a { color: #2BA6CB;}

  .btn {
    text-decoration:none;
    color:#FFF;
    background-color: #1da1db;
    width:80%;
    padding:15px 10%;
    font-weight:bold;
    text-align:center;
    cursor:pointer;
    display:inline-block;
    border-radius: 5px;
    box-shadow: 3px 3px 3px 1px #EBEBEB;
  }

  p.callout {
    padding:15px;
    text-align:center;
    background-color:#ECF8FF;
    margin-bottom: 15px;
  }
  .callout a {
    font-weight:bold;
    color: #2BA6CB;
  }

  .column table { width:100%;}
  .column {
    width: 300px;
    float:left;
  }
  .column tr td { padding: 15px; }
  .column-wrap {
    padding:0!important;
    margin:0 auto;
    max-width:600px!important;
  }
  .columns .column {
    width: 280px;
    min-width: 279px;
    float:left;
  }
  table.columns, table.column, .columns .column tr, .columns .column td {
    padding:0;
    margin:0;
    border:0;
    border-collapse:collapse;
  }

  /* -------------------------------------
  HEADER
  ------------------------------------- */
  table.head-wrap { width: 100%;}

  .header.container table td.logo { padding: 15px; }
  .header.container table td.label { padding: 15px; padding-left:0px;}


  /* -------------------------------------
  BODY
  ------------------------------------- */
  table.body-wrap { width: 100%;}


  /* -------------------------------------
  FOOTER
  ------------------------------------- */
  table.footer-wrap { width: 100%;	clear:both!important;
  }
  .footer-wrap .container td.content  p { border-top: 1px solid rgb(215,215,215); padding-top:15px;}
  .footer-wrap .container td.content p {
    font-size:10px;
    font-weight: bold;

  }


  /* -------------------------------------
  TYPOGRAPHY
  ------------------------------------- */
  h1,h2,h3,h4,h5,h6 {
    font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; line-height: 1.1; margin-bottom:15px; color:#000;
  }
  h1 small, h2 small, h3 small, h4 small, h5 small, h6 small { font-size: 60%; color: #6f6f6f; line-height: 0; text-transform: none; }

  h1 { font-weight:200; font-size: 44px;}
  h2 { font-weight:200; font-size: 37px;}
  h3 { font-weight:500; font-size: 27px;}
  h4 { font-weight:500; font-size: 23px;}
  h5 { font-weight:900; font-size: 17px;}
  h6 { font-weight:900; font-size: 14px; text-transform: uppercase; color:#444;}

  .collapse { margin:0!important;}

  p, ul {
    margin-bottom: 10px;
    font-weight: normal;
    font-size:14px;
    line-height:1.6;
  }
  p.lead { font-size:17px; }
  p.last { margin-bottom:0px;}

  ul li {
    margin-left:5px;
    list-style-position: inside;
  }

  hr {
    border: 0;
    height: 0;
    border-top: 1px dotted rgba(0, 0, 0, 0.1);
    border-bottom: 1px dotted rgba(255, 255, 255, 0.3);
  }


  /* -------------------------------------
  Shopify
  ------------------------------------- */

  .products {
    width:100%;
    height:40px;padding
    margin:10px 0 10px 0;
  }
  .products img {
    float:left;
    height:40px;
    width:auto;
    margin-right:20px;
  }
  .products span {
    font-size:17px;
  }


  /* ---------------------------------------------------
  RESPONSIVENESS
  Nuke it from orbit. It's the only way to be sure.
  ------------------------------------------------------ */

  /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
  .container {
    display:block!important;
    max-width:600px!important;
    margin:0 auto!important; /* makes it centered */
    clear:both!important;
  }

  /* This should also be a block element, so that it will fill 100% of the .container */
  .content {
    padding: 15px 15px 0 15px;
    max-width:600px;
    margin:0 auto;
    display:block;
  }

  /* Let's make sure tables in the content area are 100% wide */
  .content table { width: 100%; }

  /* Be sure to place a .clear element after each set of columns, just to be safe */
  .clear { display: block; clear: both; }


  /* -------------------------------------------
  PHONE
  For clients that support media queries.
  Nothing fancy.
  -------------------------------------------- */
  @media only screen and (max-width: 600px) {

    a[class='btn'] { display:block!important; margin-bottom:10px!important; background-image:none!important; margin-right:0!important;}

    div[class='column'] { width: auto!important; float:none!important;}

    table.social div[class='column'] {
      width:auto!important;
    }

  }

  </style>
  </head>

  <body bgcolor='#FFFFFF'>\
  <table class='head-wrap' bgcolor='#FFFFFF'>
  <tr>
  <td></td>
  <td class='header container'>

  <div class='content'>
  <table bgcolor='#FFFFFF'>

  </table>
  </div>

  </td>
  <td></td>
  </tr>
  </table>
  <table class='body-wrap'>
  <tr>
  <td></td>
  <td class='container' bgcolor='#FFFFFF'>



  <img src='cid:".$cid_logo."' alt='logo Telkomsigma' />
  <h3>Hi ,</h3>
  <h4 align='center'> You are invited on project member ".$project_name."</h4>

  <a class='btn' style='background-color: #1da1db; border-radius: 3px;
  box-shadow: 3px 3px 10px 3px #B7B7B7;' href='".base_url()."Detail_Project/view/".$project_id."'> You are invited on project member ".$project_name." &raquo;</a>


  <br/>
  <p style='text-align: left'>Trouble activating? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>


  </table>

  <!-- /BODY -->

  <!-- FOOTER -->
  <table class='footer-wrap' bgcolor='#FFFFFF'>
  <tr>
  <td></td>
  <td class='container'>

  <!-- content -->
  <div class='content' style='margin-top: -15px'>
  <table>
  <tr>
  <br/>

  </br/>
  </tr>
  </table>
  </div>
  <!-- /content -->

  </td>
  <td></td>
  </tr>
  </table>

  </body>

  </html>");
$this->email->send();
        // if($this->email->send()){
        //     //echo "sent ".$this->email->print_debugger();
        // }

    }
    public function availableMember($project_id){
      $project=$this->M_project->getProject($project_id);
      $bucode=array();
      $buid=array();
      $data['data']=array();
      $bucode[0]=$project['BU_CODE'];
      $related_bu=explode(',',$project['RELATED_BU']);
      foreach ($related_bu as $r) {
        if ($r!=$bucode[0]) {
          array_push($bucode,$r);
        }
      }
      foreach ($bucode as $bc) {
        array_push($buid,$this->M_project->getBUID($bc));
      }
      foreach ($buid as $bi) {
        if($bi!=null){
          $userl=$this->M_project->getUser($bi);
          foreach ($userl as $u) {
            array_push($data['data'],$u);
          }
        }
      }
      $userext=$this->M_project->getUserExt();
      foreach ($userext as $e) {
        array_push($data['data'],$e);
      }
      $userexist=$this->M_project->getUserInProject($project_id);
      $data['exist']=$userexist;
      $data['status']='Success';
      $data['project_id']=$project_id;
      echo json_encode($data);
    }
}
