<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Datamaster extends CI_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_session');
        $this->load->model('M_register');
        $this->load->model('M_holiday');
        $this->load->model('M_user');
        $this->load->model('M_business');
        $this->load->model('M_mis');
        $this->load->model('M_project_type');
        // $this->load->library('PHPExcel');
        // $this->load->library('PHPExcel/IOFactory');

        error_reporting(E_ALL & ~E_NOTICE);


        //TOKEN LOGIN CHECKER
        if(isset($_GET['token'])){
            $datauser["data"] = $this->M_session->GetDataUser($_GET['token']);

            $decoded_user_data =$datauser["data"];
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

        if(!isset($this->datajson['userdata']['PROF_ID']))
        {
            $this->datajson['userdata']['PROF_ID'] = 7;
        }

        //newest
        /*FOR PRIVILEGE*/
        /*===============================================================================*/
        //PRIVILEGE CHECKER

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
    public function getData($type,$pagenum=10,$page=1,$keyword=null){
        switch ($type) {
            case 'bu':
                $result[$type]= $this->getbu($keyword);
                break;
            case 'user':
                $result[$type]=  $this->getuser($keyword,$page,$pagenum);
                break;
            case 'customer':
                $result[$type]=   $this->getcustomer();
                break;
            case 'partner':
                $result[$type]=   $this->getpartner();
                break;
            case 'holiday':
                $result[$type]=   $this->getholiday($keyword);
                break;
            case 'project_type':
                $result[$type]=  $this->getproject_type($keyword);
                break;
            default:
                # code...
                break;
        }
        echo json_encode($result);
    }
    public function getbu($keyword=null){
        $bu=$this->M_business->buListDet($keyword);
        return $bu;
    }
    public function getuser($keyword=null,$page=1,$pagenum=10){
        $start = ($page*$pagenum)-$pagenum;
        $end = ($page*$pagenum);
        $user=$this->M_user->user_List($keyword);
        return $user;
    }
    public function getholiday($keyword=null){
        $holiday=$this->M_holiday->selectHoliday($keyword);
        return $holiday;
    }
    public function getproject_type($keyword=null){
        $data=$this->M_project_type->selectProjectType($keyword);
        return $data;
    }
    public function getcustomer(){
        $data=$this->M_mis->getcustomerMIS();
        return $data;
    }
    public function getpartner(){
        $data=$this->M_mis->getpartnerMIS();
        return $data;
    }
    public function manage($type,$action,$other=null){
        switch ($type) {
            case 'bu':
                return   $this->bu($action);
                break;
            case 'user':
                return    $this->user($action,$other);
                break;
            case 'holiday':
                return   $this->holiday($action);
                break;
            default:
                # code...
                break;
        }
    }
    public function bu($action){
        switch ($action) {
            case 'add':
                /*method : POST
                # Data
                ('BU_CODE');
                ('BU_PARENT_ID'); --> BU_ID yg menjadi parent BU tersebut
                ('BU_NAME');
                ('BU_ALIAS');
                ('BU_HEAD') --> user_id di table users
                */
                // check apakah BU CODE sudah digunakan
                $checkCode=$this->M_business->checkExist('BU_CODE',$this->input->post('BU_CODE'));
                // check apakah BU ALIAS sudah digunakan
                $checkAlias=$this->M_business->checkExist('BU_ALIAS',$this->input->post('BU_ALIAS'));

                if($checkCode=='code_dup'){
                    $c['status']='Error';
                    $c['msg']='Duplicate BU Code data';
                    $c['data']=null;
                }elseif($checkAlias=='alias_dup'){
                    $c['status']='Error';
                    $c['msg']='Duplicate BU Alias data';
                    $c['data']=null;
                }else{
                    $c['status']='Success';
                    $c['msg']='BU added';
                    $c['data']=$this->M_business->addNewBU();
                }
                break;
            case 'update':
                /*method : POST
                # Data
                ('BU_CODE');
                ('BU_ID');
                ('BU_NAME');
                ('BU_ALIAS');
                ('BU_HEAD') --> user_id di table users
                */
                $c['data']=$this->M_business->updateBUData();
                //check apakah data terupdate
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='Data updated';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }

                break;
            case 'changelevel':
                /*method : POST
                # Data
                ('BU_ID'); --> BU_ID yg menjadi parent BU tersebut
                ('BU_PARENT_ID');
                */
                $c['data']=$this->M_business->updateBULevel();
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='BU level updated';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }
                break;
            case 'toggleactive':
                /*method : POST
                # Data
                ('BU_ID'); --> BU_ID yg menjadi parent BU tersebut
                ('STAT'); --> 0 untuk deactive, 1 untuk active
                */
                $c['data']=$this->M_business->updateBUStatus();
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='BU status updated';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }
                break;
            default:
                # code...
                break;
        }
        echo json_encode($c);
    }
    public function user($type,$action){
        switch ($type) {
            case 'int':
                switch ($action) {
                    case 'emailactivation':
                        $type=$this->input->post('STAT');
                        $user_id=$this->input->post('USER_ID');
                        $email=$this->M_user->getEmail($user_id);
                        $c['user_id']=$user_id;
                        $c['email_to']=$email;
                        switch($type){
                            case '1':
                                $this->M_user->deleteIdentifier($email);
                                $this->M_user->createIdentifier($email);
                                $name=$this->M_user->getName($email);
                                $c['data']=$this->M_user->sendVerification($email,$name);
                                if ($c['data']) {
                                    $c['status']='Success';
                                    $c['msg_email']='Email sent, waiting user to click activation link';
                                }else{
                                    $c['status']='Error';
                                    $c['msg_email']='Cannot send email activation';
                                }
                                break;
                            case '0':
                                $name=$this->M_user->getName($email);
                                $this->M_user->deactivateUser($email);
                                $c['data']=$this->M_user->sendDeactivateInfo($email,$name);
                                if ($c['data']) {
                                    $c['status']='Success';
                                    $c['msg_email']='Email info sent';
                                }else{
                                    $c['status']='Error';
                                    $c['msg_email']='Cannot send email deactivation';
                                }
                                break;
                        }
                        break;
                    case 'autoactivation':
                        $user_id=$this->input->post('USER_ID'); //ambil data user_id vendor
                        $emailv=$this->M_user->getEmail($user_id);
                        $sup_id=$this->M_user->getSupID($user_id); //ambil data sup_id
                        $email=$this->M_user->getEmailSupID($sup_id); //ambil data email
                        $this->M_user->deleteIdentifier($emailv);
                        $name=$this->M_user->getName($email);
                        $namevendor=$this->M_user->getNameVendor($emailv);
                        $this->M_user->statusActive($user_id);
                        $c['data']=$this->M_user->sendVerificationManual($email,$name,$namevendor);
                        if ($c['data']) {
                            $c['status']='Success';
                            $c['msg_email']='Email info sent';
                        }else{
                            $c['status']='Error';
                            $c['msg_email']='Cannot send email deactivation';
                        }
                        break;
                    case 'download':
                        $filename ="Resource Internal.xls";
                        header('Content-type: application/ms-excel');
                        header('Content-Disposition: attachment; filename='.$filename);
                        echo
                        "<table class='table table-bordered box-shadow--dp responsive' id=''>
                        <thead>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Business Unit</th>
                        <th>Last Login</th>
                        <th>Profile</th>
                        <th>Status</th>
                        </thead>
                        <tbody>";
                        $ambildata = $this->M_user->ExportDatatoExcelIn();
                        foreach ($ambildata as $frow) {
                          echo "<tr>";
                          echo"<td>".$frow->USER_ID."</td>";
                          echo"<td>".$frow->USER_NAME."</td>";
                          echo"<td>".$frow->EMAIL."</td>";
                          echo"<td>".$frow->BU_NAME."</td>";
                          echo"<td>".$frow->LAST_LOGIN."</td>";
                          echo"<td>".$frow->PROF_NAME ."</td>";
                          echo"<td>". $frow->STATUS."</td>";
                          echo "</tr>";
                        }
                        echo "</tbody></table>";
                        break;
                    default:
                        # code...
                        break;
                }
                break;
            case 'ext':
                switch ($action) {
                    case 'emailactivation':
                        $type=$this->input->post('STAT');
                        $user_id=$this->input->post('USER_ID');
                        $email=$this->M_user->getEmail($user_id);
                        $c['user_id']=$user_id;
                        $c['email_to']=$email;
                        switch($type){
                            case '1':
                                $this->M_user->deleteIdentifier($email);
                                $this->M_user->createIdentifier($email);
                                $name=$this->M_user->getName($email);
                                $c['data']=$this->M_user->sendVerification($email,$name);
                                if ($c['data']) {
                                    $c['status']='Success';
                                    $c['msg_email']='Email sent, waiting user to click activation link';
                                }else{
                                    $c['status']='Error';
                                    $c['msg_email']='Cannot send email activation';
                                }
                                break;
                            case '0':
                                $name=$this->M_user->getName($email);
                                $this->M_user->deactivateUser($email);
                                $c['data']=$this->M_user->sendDeactivateInfo($email,$name);
                                if ($c['data']) {
                                    $c['status']='Success';
                                    $c['msg_email']='Email info sent';
                                }else{
                                    $c['status']='Error';
                                    $c['msg_email']='Cannot send email deactivation';
                                }
                                break;
                        }
                        break;
                    case 'autoactivation':
                        $user_id=$this->input->post('USER_ID'); //ambil data user_id vendor
                        $emailv=$this->M_user->getEmail($user_id);
                        $sup_id=$this->M_user->getSupID($user_id); //ambil data sup_id
                        $email=$this->M_user->getEmailSupID($sup_id); //ambil data email
                        $this->M_user->deleteIdentifier($emailv);
                        $name=$this->M_user->getName($email);
                        $namevendor=$this->M_user->getNameVendor($emailv);
                        $this->M_user->statusActive($user_id);
                        $c['data']=$this->M_user->sendVerificationManual($email,$name,$namevendor);
                        if ($c['data']) {
                            $c['status']='Success';
                            $c['msg_email']='Email info sent';
                        }else{
                            $c['status']='Error';
                            $c['msg_email']='Cannot send email deactivation';
                        }
                        break;
                    case 'download':
                        $filename ="Resource External.xls";
                        header('Content-type: application/ms-excel');
                        header('Content-Disposition: attachment; filename='.$filename);
                        echo
                        "<table class='table table-bordered box-shadow--dp responsive' id=''>
                        <thead>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Business Unit</th>
                        <th>Last Login</th>
                        <th>Profile</th>
                        <th>Status</th>
                        </thead>
                        <tbody>";
                        $ambildata = $this->M_user->ExportDatatoExcelExt();
                        foreach ($ambildata as $frow) {
                          echo "<tr>";
                          echo"<td>".$frow->USER_ID."</td>";
                          echo"<td>".$frow->USER_NAME."</td>";
                          echo"<td>".$frow->EMAIL."</td>";
                          echo"<td>".$frow->BU_NAME."</td>";
                          echo"<td>".$frow->LAST_LOGIN."</td>";
                          echo"<td>".$frow->PROF_NAME ."</td>";
                          echo"<td>". $frow->STATUS."</td>";
                          echo "</tr>";
                        }
                        echo "</tbody></table>";
                        break;
                    default:
                        # code...
                        break;
                }
                break;
            default:
                # code...
                break;
        }

        echo json_encode($c);
    }
    public function holiday($action){
        switch ($action) {
            case 'add':
                $id = $this->M_holiday->getMaxHoliday();
                $data['HOLIDAY_ID'] 		    = $id;
                $data['HOLIDAY'] 			      = $this->input->post("HOLIDAY");
                $data['HOLIDAY_START'] 			= "TO_DATE('".$this->input->post("HOLIDAY_START")."','yyyy-mm-dd')";
                $data['HOLIDAY_END'] 			  = "TO_DATE('".$this->input->post("HOLIDAY_END")."','yyyy-mm-dd')";
                if (isset($_POST['COLOR'])||!empty($_POST['COLOR'])) {
                    $data['COLOR'] 			      = $this->input->post("COLOR");
                }else{
                    $data['COLOR'] 			      = null;
                }
                $c['data']=$this->M_holiday->insertHoliday($data);
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='Data inserted';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }
                break;
            case 'update':
                $data['HOLIDAY_ID'] 		= $this->input->post("HOLIDAY_ID");
                $data['HOLIDAY'] 			  = $this->input->post("HOLIDAY");
                $data['HOLIDAY_START'] 	= $this->input->post("HOLIDAY_START");
                $data['HOLIDAY_END'] 		= $this->input->post("HOLIDAY_END");
                if (isset($_POST['COLOR'])||!empty($_POST['COLOR'])) {
                    $data['COLOR'] 			      = $this->input->post("COLOR");
                }else{
                    $data['COLOR'] 			      = null;
                }
                $c['data']=$this->M_holiday->editHoliday($data);
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='Data updated';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }
                break;
            case 'delete':
                $id		= $this->input->post("HOLIDAY_ID");
                $this->M_holiday->deleteHoliday($id);
                $c['status']='Success';
                $c['msg']='Data deleted';
                break;
            default:
                # code...
                break;
        }
        echo json_encode($c);
    }
    public function test(){
        $data=array(
            array('id'=>0,'parent'=>null,'level'=>null,'leaf'=>0),
            array('id'=>1,'parent'=>0,'level'=>1,'leaf'=>1),
            array('id'=>2,'parent'=>0,'level'=>1,'leaf'=>0),
            array('id'=>3,'parent'=>2,'level'=>2,'leaf'=>0),
            array('id'=>4,'parent'=>3,'level'=>3,'leaf'=>1),
            array('id'=>5,'parent'=>3,'level'=>3,'leaf'=>1),
            array('id'=>6,'parent'=>2,'level'=>2,'leaf'=>1),
            array('id'=>7,'parent'=>0,'level'=>1,'leaf'=>1)
        );
        echo json_encode($data);
        $node=1;
        $seq=1;
        $next=1;
        $lv=1;
        echo '<br>';
        foreach ($data as $d) {
            for ($i=0; $i <$d['level'] ; $i++) {
                // if ($lv==$d['level']) {
                //   $next++;
                // }
                // if ($i==$d['level']-1) {
                //   $node.='.'.$next;
                // }else{
                //   $node.='.'.$seq;
                // }
                $node.=".";
            }
            echo($d['id']).'=== '.$node.'<br>';
        }
    }
}
