<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Task extends CI_Controller
{

    private $datajson = [];
    function __construct()
    {
        parent::__construct();
        $this->datajson['privilege'] = ['master_data_access'=>false,
        'manage_role_access'=>false,
        'create_edit_delete_task_updatepercent'=>false,
        'req_rebaseline'=>false,
        'acc_deny_rebaseline'=> false,
        'assign_project_member'=>false,
        'project_report'=>true,
        'project_activities'=>false,
        'acc_deny_timesheet'=>false,
        'report_overview'=>false];
        error_reporting(E_ALL & ~E_NOTICE);

        $this->load->model('M_detail_project');
        $this->load->model('M_session');
        $this->load->helper('file');

        //TOKEN LOGIN CHECKER
        if(isset($_GET['token'])){
            $datauser["data"] = $this->M_session->GetDataUser($_GET['token']);

            $decoded_user_data =$datauser['data'];
            //    print_r($decoded_user_data);
            $this->datajson['token'] = $_GET['token'];
        }
        elseif(isset($_SERVER['HTTP_TOKEN'])){
            $decoded_user_data = $this->M_session->GetDataUser($_SERVER['HTTP_TOKEN']);
            $this->datajson['token'] = $_SERVER['HTTP_TOKEN'];
        }
        else{
            $error['error']="Login First!";
            echo json_encode($error);
            die();
        }
        //if login success
        if(count($decoded_user_data) > 0){
            //get user data from token
            //for login bypass ,this algorithm is not used
            //$this->datajson['userdata'] = (array)$decoded_user_data['data'];
            //this code below for login bypass
            $this->datajson['userdata'] = $decoded_user_data;
        }
        //if login fail
        else {
            $returndata['login_error'] = 'Login Failed';
            echo json_encode($returndata);
            die();
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
                            //for if tolerant array_search
                            $directorat_bu[] = null;
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
                                break;
                                //Create Project
                                case '3':
                                $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['BU']."'")->row()->BU_ID;
                                break;
                                //Access All Project In Business Unit
                                case '4' :
                                $projectid = $this->uri->segment(3);
                                $bu_id = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$projectid'")->row()->BU_ID;
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
                            if(!((array_search($bu_id,$directorat_bu) != null|| $bu_id == 'masuk') && $bu_id != null)){
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
                        $granted_project_list[] = null;

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

        if($this->datajson['userdata']['PROF_ID'] == 7){
            $this->datajson['privilege']['master_data_access'] = true;
            $this->datajson['privilege']['manage_role_access'] = true;
            $this->datajson['privilege']['report_overview'] = true;
        }
        if($this->datajson['userdata']['PROF_ID'] == 5){
            $this->datajson['privilege']['create_edit_delete_task_updatepercent']=true;
            $this->datajson['privilege']['req_rebaseline']=true;
            $this->datajson['privilege']['create_edit_delete_task_updatepercent']=true;
            $this->datajson['privilege']['assign_project_member']=true;
            $this->datajson['privilege']['project_activities']=true;
            $this->datajson['privilege']['acc_deny_timesheet']=true;
        }
        /*===============================================================================*/

    }

    /*START TASK MANAJEMENT*/
    //Task View
    function workplan_view(){
        $id_project = $this->uri->segment(3);
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$id_project'")->row()->RH_ID;
        $workplan=$this->M_detail_project->selectWBS($id_project,$rh_id);
        foreach ($workplan as &$wp){
            if($wp['WORK_PERCENT_COMPLETE'] == null){
                $wp['WORK_PERCENT_COMPLETE'] = 0;
            }
            if($wp['WORK'] == null){
                $wp['WORK'] = 0;
            }
        }

        $rebaseline = $this->M_detail_project->getRebaselineTask($rh_id);

        //$created_array = $this->buildTree($workplan);

        //built tree
        foreach($workplan as $row) {
            $row['children'] = array();
            $vn = "row" . $row['WBS_ID'];
            ${$vn} = $row;
            if(!is_null($row['WBS_PARENT_ID'])) {
                $vp = "parent" . $row['WBS_PARENT_ID'];
                if(isset($data[$row['WBS_PARENT_ID']])) {
                    ${$vp} = $data[$row['WBS_PARENT_ID']];
                }
                else {
                    ${$vp} = array('n_id' => $row['WBS_PARENT_ID'], 'WBS_PARENT_ID' => null, 'WBS_PARENT_ID' => array());
                    $data[$row['WBS_PARENT_ID']] = &${$vp};
                }
                ${$vp}['children'][] = &${$vn};
                $data[$row['WBS_PARENT_ID']] = ${$vp};
            }
            $data[$row['WBS_ID']] = &${$vn};
        }


        //for null data tolerance
        if($data == null){
            $data = [];
        }
        $result = array_filter($data, function($elem) { return is_null($elem['WBS_PARENT_ID']); });
        $result['workplan'] = $result[$id_project.'.0'];
        $result['rebaseline_task'] = $rebaseline;
        unset($result[$id_project.'.0']);
        $result['project_status'] = $this->db->query("select project_status from projects where project_id = '$id_project'")->row()->PROJECT_STATUS;
        echo json_encode($result);


        //echo var_dump($workplan);
    }
    function workplan_view_mobile(){
        $id_project = $this->uri->segment(3);
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$id_project'")->row()->RH_ID;
        $workplan=$this->M_detail_project->selectWBS_mobile($id_project,$rh_id);
        foreach ($workplan as &$wp){
            if($wp['WORK_PERCENT_COMPLETE'] == null){
                $wp['WORK_PERCENT_COMPLETE'] = 0;
            }
            if($wp['WORK'] == null){
                $wp['WORK'] = 0;
            }
        }

        $rebaseline = $this->M_detail_project->getRebaselineTask($rh_id);

        $result['workplan'] = $workplan;
        $this->transformKeys($result);
        $result['rebaseline_task'] = $rebaseline;
        echo json_encode($result);


        //echo var_dump($workplan);
    }



    //Create Task
    function createTask_view($project_id){
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
        $data['parent']=$this->db->query("select wbs_id,wbs_name,rebaseline 
            from (select wbs_id,wbs_name,project_id,wbs_parent_id,'no' as rebaseline from wbs 
            union
            select wbs_id,wbs_name,project_id,wbs_parent_id,'yes' as rebaseline from temporary_wbs where rh_id = '$rh_id') 
            where PROJECT_ID='".$project_id."' connect by  wbs_parent_id= prior wbs_id start with wbs_id='".$project_id.".0' order siblings by wbs_parent_id")->result_array();
        echo json_encode($data);
    }
    function createTask(){
        $project_id   = $this->input->post("PROJECT_ID");

        $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;
        $statusProject =strtolower($statusProject);

        if($statusProject == 'on hold'){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
            //wbs id same with project id
            $data['WBS_NAME'] = $this->input->post("WBS_NAME");
            $data['WBS_ID'] = $project_id;
            $data['WBS_PARENT_ID'] = $this->input->post("WBS_PARENT_ID");
            $data['START_DATE']   = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
            $data['FINISH_DATE']  ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";


            // insert into wbs and get new ID
            $newid = $this->M_detail_project->insertWBSTemp($data,$project_id,$rh_id);
            $status['status'] = 'success';
            $status['message'] = 'Task berhasil di tambah temporary';
        }
        elseif($statusProject == 'not started'){
            //wbs id same with project id
            $data['WBS_NAME'] = $this->input->post("WBS_NAME");
            $data['WBS_ID'] = $project_id;
            $data['WBS_PARENT_ID'] = $this->input->post("WBS_PARENT_ID");
            $data['START_DATE']   = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
            $data['FINISH_DATE']  ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";

            // insert into wbs and get new ID
            $newid = $this->M_detail_project->insertWBS($data,$project_id);

            $WP_ID= $this->M_detail_project->getMaxWPID();
            $RP_ID= $this->M_detail_project->getMaxRPID();

            //get all wbs data from new wbs
            $selWBS=$this->M_detail_project->getWBSselected($newid);
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

            $status['status'] = 'success';
            $status['message'] = 'Task berhasil di tambah';
        }
        else{
            $this->output->set_status_header(400);
            $status['status'] = 'failed';
            $status['message'] = "Status Project anda $statusProject";
        }
        echo json_encode($status);
    }

    //EDIT TASK
    function editTask_view($wbs_id)
    {
        $project_id = explode(".",$wbs_id);
        $query = $this->db->query("select * from wbs where WBS_ID='".$wbs_id."'");
        $data['detail_task'] = $query->result_array();
        $data['parent']=$this->db->query("select wbs_id,wbs_name,rebaseline from (select wbs_id,wbs_name,project_id,wbs_parent_id,'no' as rebaseline from wbs union select wbs_id,wbs_name,project_id,wbs_parent_id,'yes' as rebaseline from temporary_wbs) where PROJECT_ID='".$project_id[0]."' connect by  wbs_parent_id= prior wbs_id start with wbs_id='".$project_id[0].".0' order siblings by wbs_parent_id")->result_array();
        echo json_encode($data);
    }

    function editTask_action(){


        $project_id   = $this->input->post("project_id");

        $statusProject = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);
        if($statusProject == 'on hold'){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
            $wbs=$this->input->post("WBS_ID");
            $this->M_detail_project->Edit_WBSTemp(
                $_POST["wbs_id"],
                $_POST["wbs_parent_id"],
                $_POST["project_id"],
                $_POST["wbs_name"],
                $_POST['start_date'],
                $_POST['finish_date'],
                $rh_id
                );
            $status['status']= 'success';
            $status['message'] = 'Task berhasil di edit temporary';

        }
        elseif($statusProject == 'not started'){
            $wbs=$this->input->post("WBS_ID");
            $this->M_detail_project->Edit_WBS(
                $_POST["wbs_id"],
                $_POST["wbs_parent_id"],
                $_POST["project_id"],
                $_POST["wbs_name"],
                $_POST['start_date'],
                $_POST['finish_date']
                );
            //$this->M_detail_project->insertWBS($data,$project_id);
            //$WP_ID= $this->M_detail_project->getMaxWPID();
            //$RP_ID= $this->M_detail_project->getMaxRPID();
            //$this->M_detail_project->insertWBSPool($data,$RP_ID,$WP_ID,$project_id);
            $selWBS=$this->getSelectedWBS($wbs);
            $allParent=$this->getAllParent($selWBS->WBS_ID);
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
            $status['status']= 'success';
            $status['message'] = 'Task berhasil di edit';
        }
        else{
            $name = $this->input->post('wbs_name');
            $wbs_id = $this->input->post('wbs_id');
            $this->db->query("update wbs set wbs_name = '$name' where wbs_id = '$wbs_id'");
            if($this->db->affected_rows() == 1){
                $status['status']= 'Succes';
                $status['message'] = 'Task Name Updated';
            }
            else{
                $this->output->set_status_header(500);
                $status['status']= 'failed';
                $status['message'] = 'Task name not updated';
            }

        }
        echo json_encode($status);

    }

    //delete task
    public function deleteTask()
    {

        $id = $_POST['wbs_id'];
        $wbs_id = $_POST['wbs_id'];
        $project_id = $this->M_detail_project->getProjectTask($id);
        $statusProject = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);
        if($statusProject == 'on hold'){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
            $this->M_detail_project->updateProgressDeleteTaskTemp($wbs_id,$rh_id);
            $returndata['status'] = "success";
            $returndata['message'] = "Task temporary deleted success";
        }
        elseif($statusProject == 'not started'){
            //$this->M_detail_project->deleteWBSID($id);
            //$this->M_detail_project->deleteWBSPoolID($id);
            $this->M_detail_project->updateProgressDeleteTask($wbs_id);
            $returndata['status'] = "success";
            $returndata['message'] = "Task delete success";
        }
        else{
            $returndata['status'] = "failed";
            $returndata['message'] = "Project still on progress";
        }


        echo json_encode($returndata);
    }

    //Update Task Complete Percent
    public function editTaskPercent(){
        $data['WBS_ID']=$this->input->post("WBS_ID");
        $data['PROJECT_ID']=$this->input->post("PROJECT_ID");
        $data['WORK_PERCENT_COMPLETE']=$this->input->post("WORK_PERCENT_COMPLETE");

        //data di null kan , supaya input di modal berhasil
        $data['DESCRIPTION']=$this->input->post("DESCRIPTION");
        $data['DATE']=date("d/m/Y");
        $data['USER_ID']=$this->datajson['userdata']['USER_ID'];
        $this->M_detail_project->UpdatePercentWBS($data);

        $returndata['status'] = "success";
        echo json_encode($returndata);
    }

    //View Edit task member project
    public function assignTaskMember_view(){
        $project=$this->input->post('PROJECT_ID');
        $wbs_id=$this->input->post('WBS_ID');
        $data['task_name'] = $this->M_detail_project->getWBSselected($wbs_id)->WBS_NAME;
        $data['available_to_assign'] = $this->M_detail_project->getWBSAvailableUser($project,$wbs_id);
        $data['currently_assigned']=$this->M_detail_project->getWBSselectedUser($project,$wbs_id);
        $data['rebaseline'] = $this->db->query("
            SELECT RESOURCE_POOL.RP_ID, users.user_name,users.email,'yes' as rebaseline,action FROM RESOURCE_POOL
            join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
            join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
            join TEMPORARY_WBS_POOL on TEMPORARY_WBS_POOL.RP_ID = RESOURCE_POOL.RP_ID
            WHERE PROJECT_ID='$project' and RESOURCE_POOL.user_id in(
            select user_id
            from temporary_wbs_pool
            inner join resource_pool
            on temporary_wbs_pool.rp_id=resource_pool.rp_id
            where wbs_id='$wbs_id')
            group by RESOURCE_POOL.RP_ID, users.user_name,users.email,action")->result_array();
        echo json_encode($data);
    }

    //Remove task from task member
    public function removeTaskMemberProject(){

        $project_id = explode(".",$_POST['WBS_ID']);
        $project_id = $project_id[0];
        $statusProject = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);

        if($statusProject == 'on hold'){
            $this->M_detail_project->removeAssignementTemp();
            $data['status'] = 'success';
            $data['message'] = 'Task member berhasil di hapus temporary';
        }
        elseif($statusProject['not started']){
            $this->M_detail_project->removeAssignement();

            //send email
            $email=$this->input->post('EMAIL');
            $user_name=$this->input->post('NAME');
            $wbs_name=$this->input->post('WBS_NAME');
            //$this->sendVerificationremoveMember($email,$user_name,$wbs_name);
            $data['status'] = 'success';
            $data['message'] = 'Task member berhasil di hapus';
        }
        else{

            $data['status'] = 'failed';
            $data['message'] = 'Project status masih on progress';
        }
        echo json_encode($data);
    }

    //Assign task to project member
    public function assignTaskMemberProject(){
        $project_id = explode(".",$_POST['WBS_ID']);
        $project_id = $project_id[0];
        $statusProject = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);
        if($statusProject == 'on hold'){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
            $this->M_detail_project->postAssignmentTemp($rh_id);
            $data['status'] = 'success';
            $data['message'] = 'member di tambah temporary';
        }
        elseif($statusProject == 'not started'){
            //assign process
            $this->M_detail_project->postAssignment();
            //send email
            $wbs=$this->input->post('WBS_ID');
            $email=$this->input->post('EMAIL');
            $user_name=$this->input->post('NAME');
            $wbs_name=$this->input->post('WBS_NAME');
            $projectid = $this->M_detail_project->getProject_Id($wbs);
            //$this->sendVerificationassignMember($email,$user_name,$wbs_name,$projectid);$data['status'] = 'success';
            $data['status'] = 'success';
            $data['message'] = 'member di tambah';
        }
        else{
            $data['status'] = 'failed';
            $data['message'] = 'Project status masih on progress';
        }
        //return
        echo json_encode($data);

    }


    public function upload_wbs() {

        $config['upload_path'] = 'document_assets/temp_upload';
        $config['allowed_types'] = 'xlsx|xls|csv';

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('document'))
        {
            $data = array('error' => $this->upload->display_errors());
            //    $data['nav']=($this->load->view('v_nav1'));
            //  $data['header']=($this->load->view('v_header'));

            $datajson['status'] = "failed";
            $datajson['message']= "gagal upload file";
            //echo "<script>alert('berhasil')</script>";
            //$this->load->view('uploadwbs', $data);

            //$this->load->view('uploadwbs', $data);
            //redirect('/Importwbs');
            echo json_encode($data);
        }
        else
        {
            $this->load->model('M_wbs');
            $data = array('error' => false);
            $upload_data = $this->upload->data();
            $this->load->library('excel_reader');
            $this->excel_reader->setOutputEncoding('230787');
            $file =  $upload_data['full_path'];
            $this->excel_reader->read($file);
            error_reporting(E_ALL ^ E_NOTICE);
            // Sheet 1
            $data = $this->excel_reader->sheets[0] ;
            $coba = $data['numRows'];
            $dataexcel = Array();
            for ($i = 1; $i <= $data['numRows']; $i++) {
                if($data['cells'][$i][1] == '')
                    break;
                
                $cars = $data['cells'][$i][1];
                $array[]=$data['cells'][$i][1];
                $dataexcel[$i-1]['anjay']=$array;
                $dataexcel[$i-1]['PROJECT_ID']=$this->input->post('project_id');
        //}
        //$dataexcel[$i-1]['WBS_ID']= $data['cells'][$i][1];
        //  $dataexcel[$i-1]['WBS_PARENT_ID']= $data['cells'][$i][2];
        //$dataexcel[$i-1]['IWO_NO']= $data['cells'][$i][3];
        //$dataexcel[$i-1]['USER_ID']= $data['cells'][$i][2];
                $dataexcel[$i-1]['WBS_NAME']= $data['cells'][$i][4];
        //$dataexcel[$i-1]['WBS_DESC']= $data['cells'][$i][];
        //$dataexcel[$i-1]['PRIORITY']= $data['cells'][$i][5];
        //  $dataexcel[$i-1]['CALCULATION_TYPE']= $data['cells'][$i][6];
        //    $dataexcel[$i-1]['USER_TAG']= $data['cells'][$i][7];
        //  $dataexcel[$i-1]['PHASE']= $data['cells'][$i][8];
        //$dataexcel[$i-1]['EFFORT_DRIVEN']= $data['cells'][$i][9];
        //$dataexcel[$i-1]['WORK']=trim($data['cells'][$i][5]," hrs");
                @$dur =$this->countDuration(date_format(date_create($data['cells'][$i][6]),"Y/m/d"), date_format(date_create($data['cells'][$i][7]),"Y/m/d"));
        //$dataexcel[$i-1]['DURATION']= floor(trim($data['cells'][$i][5]," days"));
                $dataexcel[$i-1]['DURATION']= $dur;
        //$dataexcel[$i-1]['START_DATE']= date('d/m/Y',strtotime($data['cells'][$i][6]));
                @$dataexcel[$i-1]['START_DATE']= date_format(date_create($data['cells'][$i][6]),"d/m/Y");
                $dataexcel[$i-1]['START_DATEs']= $data['cells'][$i][6];
        //$dataexcel[$i-1]['ACTUAL_START_DATE']= $data['cells'][$i][11];
                @$dataexcel[$i-1]['FINISH_DATE']= date_format(date_create($data['cells'][$i][7]),"d/m/Y");
        //$dataexcel[$i-1]['FINISH_DATE']= date('d/m/Y',strtotime($data['cells'][$i][7]));
                $dataexcel[$i-1]['FINISH_DATEs']= $data['cells'][$i][7];
        //$dataexcel[$i-1]['ACTUAL_FINISH_DATE']= $data['cells'][$i][13];
                
                
        //$dataexcel[$i-1]['MILESTONE']= $data['cells'][$i][16];
        //$dataexcel[$i-1]['WORK_COMPLETE']= $data['cells'][$i][7];
        //    $dataexcel[$i-1]['WORK_PERCENT_COMPLETE']= $data['cells'][$i][18];
        //  $dataexcel[$i-1]['CONSTRAINT_TYPE']= $data['cells'][$i][19];
        //  $dataexcel[$i-1]['CONSTRAINT_DATE']= $data['cells'][$i][20];
        //  $dataexcel[$i-1]['DEADLINE']= $data['cells'][$i][21];
        //    $dataexcel[$i-1]['WBS_PARENT_ID']= $data['cells'][$i][22];
        //    $dataexcel[$i-1]['ACHIEVEMENT']= $data['cells'][$i][23];
        //  $dataexcel[$i-1]['ID']= $data['cells'][$i][24];
        //$dataexcel[$i-1]['TEXT']= $data['cells'][$i][25];
        //$dataexcel[$i-1]['PROGRESS']= $data['cells'][$i][26];
        //$dataexcel[$i-1]['SORTORDER']= $data['cells'][$i][27];
        //$dataexcel[$i-1]['PARENT']= $data['cells'][$i][28];
        //  $dataexcel[$i-1]['PLANNED_START']= $data['cells'][$i][29];
        //$dataexcel[$i-1]['PLANNED_END']= $data['cells'][$i][30];
        //$dataexcel[$i-1]['END_DATE']= $data['cells'][$i][31];
            }

            //echo json_encode($dataexcel);
            $this->M_wbs->tambahwbs($dataexcel);
            //$data['ini']=$dataexcel;
            //$data['nav']=($this->load->view('v_nav1'));
            //  $data['header']=($this->load->view('v_header'));
            delete_files($file);
            $datajson['status'] = 'success';
            $datajson['message'] = 'Data berhasil di upload';
            echo json_encode($datajson);
            //$this->session->set_flashdata("bukanpesan",json_encode($dataexcel));
            //$this->session->set_flashdata("bukanpesan",$this->M_wbs->tambahwbs($dataexcel));
            //echo "<script>alert('berhasil')</script>";
            //$this->load->view('uploadwbs', $data);
        }

    }

    //Email information remove user from task
    private function sendVerificationremoveMember($email,$user_name,$wbs_name){

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
        //$this->email->to($email);
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject('Deleting Assign From Task');
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
            table.footer-wrap { width: 100%;  clear:both!important;
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
                          <tr>
                              <td>

                              </td>

                          </tr>
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

                  <div class='content'>
                      <table>
                          <tr>
                              <td align='center'>
                              </td>
                          </tr>
                          <tr>
                              <td>
                                  <br/>
                                  <img src='cid:".$cid_logo."' alt='logo Telkomsigma' />
                                  <h2>Hi ,</h3>
                                      <br/>
                                      <h4>User ".$user_name."  You are has removed from task ".$wbs_name."</h4>
                                      <br>

                                      <br/>
                                      <p style='text-align: left'>Having Trouble ? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
                                  </td>
                              </tr>

                          </table>
                      </div>

                  </td>

              </tr>
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

if($this->email->send()){
    echo "sent ".$this->email->print_debugger();
}

}

    //Email information add user to task
private function sendVerificationassignMember($email,$user_name,$wbs_name,$projectid){


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
            //$this->email->to($email);
    $logo=base_url()."asset/image/logo_new_sigma1.png";
    $css=base_url()."asset/css/confirm.css";
    $this->email->attach($logo);
    $this->email->attach($css);
    $cid_logo = $this->email->attachment_cid($logo);
    $this->email->subject('Assign Member to Task');
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
        table.footer-wrap { width: 100%;  clear:both!important;
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
                      <tr>
                          <td>

                          </td>

                      </tr>
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

              <div class='content'>
                  <table>
                      <tr>
                          <td align='center'>
                          </td>
                      </tr>
                      <tr>
                          <td>
                              <br/>
                              <img src='cid:".$cid_logo."' alt='logo Telkomsigma' />
                              <h2>Hi ,</h3>
                                  <br/>
                                  <h4>User ".$user_name." You are has Assigned on Project task ".$wbs_name." </h4>
                                  <br>
                                  <a href = '".base_url()."Detail_Project/view/".$projectid."'>Click Here</a>
                                  <br/>
                                  <p style='text-align: left'>Having Trouble ? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
                              </td>
                          </tr>

                      </table>
                  </div>

              </td>

          </tr>
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

if($this->email->send()){
    echo "sent ".$this->email->print_debugger();
}
}

private function getSelectedWBS($id){
    return $this->M_detail_project->getWBSselected($id);
}

private function getAllParent($id){
    return $this->M_detail_project->getAllParentWBS($id);
}

function last_day($month = '', $year = '')
{
    if (empty($month))
    {
        $month = date('m');
    }

    if (empty($year))
    {
        $year = date('Y');
    }

    $result = strtotime("{$year}-{$month}-01");
    $result = strtotime('-1 second', strtotime('+1 month', $result));

    return date('Y/m/d', $result);
}
function getHolidays() {
    $var = $this->db->query("select to_char(HOLIDAY_START,'YYYY-MM-DD') as H_START, to_char(HOLIDAY_END,'YYYY-MM-DD') as H_END  from p_holiday where HOLIDAY_START is not null")->result_array();

    $w = array();
    foreach ($var as $v) {
        $x = $this->dateRange($v['H_START'], $v['H_END']);
        $w = array_merge($w, $x);
        $w = array_unique($w);
    }
            //print_r($w);
    return $w;
}

function countDuration($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');
    $interval = $end->diff($start);
            //print_r($interval);
    $days = $interval->days;
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
            //print_r($period);
    $holidays = $this->getHolidays();
    foreach ($period as $dt) {
        $curr = $dt->format('D');
        if (in_array($dt->format('Y-m-d'), $holidays)) {
            $days--;
        }
        if ($curr == 'Sat' || $curr == 'Sun') {
            $days--;
        }
    }
    return $days;
}
function countDurationAll($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');
    $interval = $end->diff($start);
    $days = $interval->days;
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    return $days;
}
function dateRange($first, $last, $step = '+1 day', $output_format = 'Y-m-d') {

    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);

    while ($current <= $last) {
        if (date("D", $current) != "Sun" && date("D", $current) != "Sat") {
            $dates[] = date($output_format, $current);
        }
        $current = strtotime($step, $current);
    }

    return $dates;
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

public function getCurrentProgresTask()
{
    $wbs_id=$_POST['wbs_id'];
    $this->db->where('WBS_ID', $wbs_id); 
    $query = $this->db->get('WBS');
    $data = $query->row();
    $current['WORK_PERCENT_COMPLETE']= $data->WORK_PERCENT_COMPLETE;
    echo json_encode($current);
}

}
