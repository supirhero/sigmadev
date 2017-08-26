<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Timesheet extends CI_Controller {

    public $datajson = array();


    function __construct()
    {
        parent::__construct();
        $this->load->model('M_session');
        $this->load->model('M_timesheet');
        $this->load->model('M_data');
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
        //get user project
        $all_user_project_id = $this->db->query("select project_id from resource_pool 
                                                                    where user_id = '".$this->datajson['userdata']['USER_ID']."'
                                                               ")->result_array();
        //store list project
        $list_project_id =[];
        foreach ($all_user_project_id as $projecti){
            array_push($list_project_id,$projecti['PROJECT_ID']);
        }
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
                            switch ($priv['ACCESS_ID']){
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
                                case '2':
                                    $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
                                    break;
                                case '3':
                                    $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['BU']."'")->row()->BU_ID;
                                    break;
                                case '4' :
                                    break;
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
                                case '6' :
                                    $databu = $this->db->query("select p_bu.bu_id,bu_parent_id from p_bu where p_bu.bu_id = '".$this->datajson['userdata']['BU_ID']."'")->row_array();
                                    if($databu['BU_ID'] == 0){
                                        $this->bu_id = $this->db->query('select bu_id from p_bu')->result_array();
                                    }
                                    elseif ($databu['BU_PARENT_ID'] == 0){
                                        $this->bu_id = $this->db->query("select bu_id from p_bu where bu_parent_id = ".$databu['BU_ID']."")->result_array();
                                    }
                                    else{
                                        $this->bu_id[0]['BU_ID'] = $this->datajson['userdata']['BU_ID'];
                                    }
                                    $bu_id = 'masuk';
                                    break;
                                case '7':
                                    $bu_id = $_POST['BU_ID'];
                                    break;
                                case '8':

                                    break;
                                case '9':
                                    $projectid = $_POST['project_id'];
                                    $databu = $this->db->query("select b.bu_id,b.bu_parent_id from projects a join p_bu b on a.bu_id = b.bu_id where project_id = '$projectid'")->row_array();
                                    if($databu['BU_ID'] == 0){
                                        $this->bu_id = $this->db->query('select bu_id from p_bu')->result_array();
                                    }
                                    elseif ($databu['BU_PARENT_ID'] == 0){
                                        $this->bu_id = $this->db->query("select bu_id from p_bu where bu_parent_id = ".$databu['BU_ID']."")->result_array();
                                    }
                                    else{
                                        $this->bu_id[0]['BU_ID'] = $this->datajson['userdata']['BU_ID'];
                                    }
                                    $bu_id='masuk';
                                    break;
                            }

                            if($this->datajson['userdata']['BU_ID'] == $bu_id || $bu_id == 'masuk'){

                            }
                            else{
                                $returndata['status'] = 'denied';
                                $returndata['message'] = 'you dont have permission to access this action';
                                echo json_encode($returndata);
                                die;
                            }


                        }
                        else{
                            $returndata['status'] = 'denied';
                            $returndata['message'] = 'you dont have permission to access this action';
                            echo json_encode($returndata);
                            die;
                        }

                    }
                    elseif($priv['TYPE'] == 'PROJECT'){
                        if($priv['PRIVILEGE'] == 'can'){
                            switch ($priv['ACCESS_ID']){
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
                                case '11':
                                    $project_id_req = explode(".",$_POST['WBS_ID']);
                                    $project_id_req = $project_id_req[0];
                                    break;
                                case '12':
                                    $project_id_req = $_POST['project_id'];
                                    break;
                                case '13':
                                    $project_id_req = $_POST['PROJECT_ID'];
                                    break;
                                case '14':
                                    $project_id_req = $_POST['project_id'];
                                    break;
                                case '15':
                                    $project_id_req = $_POST['PROJECT_ID'];
                                    break;
                                case '16':
                                    $project_id_req = $this->uri->segment(3);
                                    break;
                            }
                        }
                        else{

                        }
                        if(!in_array($project_id_req,$list_project_id)){
                            $returndata['status'] = 'denied';
                            $returndata['message'] = 'you dont have permission to access this action';
                            echo json_encode($returndata);
                            die;
                        }
                    }
                    else{
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

    //for timesheet view data
    function view(){

        //select project based on user
        $date = $this->input->post('date');

        $userid = $this->datajson['userdata']['USER_ID'];
        $project = $this->db->query("SELECT distinct project_name, project_id , project_status FROM CARI_TASK WHERE PROJECT_STATUS <> 'Completed' AND USER_ID='".$userid."'")->result_array();
        $activity = $this->M_timesheet->selectTimesheet_bydate($this->datajson['userdata']['USER_ID'],$date);

        $data = [];
        $data['user_project'] = $project;
        $data['user_activities'] = $activity;
        $data['holidays']=json_decode($this->M_data->get_holidays(),True);


        if(isset($_POST['mobile'])){
            $this->transformKeys($data);
        }

        echo json_encode($data);
    }

    //get task from project
    function taskList(){

        if(isset($_POST['mobile'])){
            $_POST = array_change_key_case($_POST,CASE_UPPER);
        }
        $id=$this->input->post("PROJECT_ID");
        $user_id = $this->datajson['userdata']['USER_ID'];
        $query = $this->db->query("SELECT WP_ID,PROJECT_ID,WBS_NAME,TASK_MEMBER_REBASELINE,TASK_REBASELINE FROM CARI_TASK_NEW WHERE PROJECT_ID='".$id."' and USER_ID='".$user_id."'");
        //$query = $this->db->query("SELECT * FROM CARI_TASK WHERE PROJECT_ID='900418' and USER_ID='S201506017'");

        $hasil['task'] = $query->result_array();

        if(isset($_POST['MOBILE'])){
            $this->transformKeys($hasil);
        }

        echo json_encode($hasil);
    }

    //get all daily task work hours total weekly
    function allTaskHourTotal(){
        // $id=$this->input->post("project_id");
        $user_id=$this->datajson['userdata']['USER_ID'];
        // $wp=$this->input->post('WP_ID');
        $dt0=$this->input->post('date_start');
        $dt6=$this->input->post('date_end');
        //$tsdate=$this->input->post('TS_DATE');
        //$id='S201506080';
        //$user_id='900207';
        $dt0=date('Y-m-d',strtotime($dt0));
        $dt6=date('Y-m-d',strtotime($dt6));


        $h=$this->M_timesheet->selectTotalHourAllProject($dt0,$dt6,$user_id);

        $hours = $this->M_timesheet->selectHourAllProject($dt0,$dt6,$user_id);


        /*
         $i=0;
        foreach ($hour as $hasilhour) {
            $data['hours'][$i]=$hasilhour['HOUR'].'.'.($hasilhour['TS_DATE']-1);
            $i++;
        }
        */
        //print_r($data['hours']);

        $data['hours'] = $hours;
        //$data['user_id']=$user_id;
        $data['total_hours']=$h;

        if(isset($_POST['mobile'])){
            $this->transformKeys($data);
        }

        echo json_encode($data);
        //echo $h;
    }

    //get task work hours -> specified task
    function taskHourTotal(){
        $id=$this->datajson['userdata']['USER_ID'];
        $dt0=$this->input->post('date_start');
        $dt6=$this->input->post('date_end');
        // $project_id =$this->input->post('PROJECT_ID');
        $wp=$this->input->post('wp_id');

        $h=$this->M_timesheet->selectTotalHour($id,$dt0,$dt6,$wp);
        $hour = $this->M_timesheet->selectHour($id,$dt0,$dt6,$wp);
        $i=0;

        foreach ($hour as $hasilhour) {
            $data['hours'][$i]=$hasilhour['HOUR'].'.'.($hasilhour['D']-1);
            $i++;
        }

        $data['wp_id']=$wp;
        $data['total_hours']=$h;
        $data['dt0']=$dt0;
        $data['dt6']=$dt6;
        echo json_encode($data);
    }

    //add timesheet
    function addTimesheet(){

        if(isset($_POST['mobile'])){
            $_POST = array_change_key_case($_POST,CASE_UPPER);
        }

        $userid=$this->datajson['userdata']['USER_ID'];
        $data['WORK_HOUR'] = $this->input->post("HOUR");
        $data['DATE'] = $this->input->post("TS_DATE");
        $data['SUBJECT'] = $this->input->post("TS_SUBJECT");
        $data['MESSAGE'] = $this->input->post("TS_MESSAGE");
        $data['LATITUDE'] = $this->input->post("LATITUDE");
        $data['LONGITUDE'] = $this->input->post("LONGITUDE");
        $data['PROJECT_ID'] = $this->input->post("PROJECT_ID");
        $data['WP_ID'] = $this->input->post("WP_ID");

        $project_id   = $_POST['PROJECT_ID'];
        $wp_id = $_POST['WP_ID'];
        $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;
        //check rebaseline status for task

        if($statusProject == 'On Hold'){

            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
            //check member wbs_pool status if it need rebaseline approval
            $checkmember = $this->db->query("
                                        select 'yes' as rebaseline
                                        from temporary_wbs_pool
                                        where wp_id = '".$_POST['WP_ID']."'
                                        and rh_id = '$rh_id'
                                        union
                                        select 'no' as rebaseline 
                                        from wbs_pool
                                        where wp_id = '".$_POST['WP_ID']."'")->row()->REBASELINE;

            //check task status if it need rebaseline approval
            if($checkmember == 'yes'){
                $checktask = $this->db->query("
                                                select rebaseline from (
                                                  select 'yes' as rebaseline 
                                                  from temporary_wbs
                                                  join temporary_wbs_pool
                                                  on temporary_wbs.wbs_id = temporary_wbs_pool.wbs_id
                                                  where temporary_wbs_pool.wp_id = '$wp_id'
                                                  and temporary_wbs.rh_id = '$rh_id'
                                                  and temporary_wbs_pool.rh_id = '$rh_id'
                                                  UNION 
                                                  select 'no' as rebaseline 
                                                  from wbs a
                                                  join temporary_wbs_pool b
                                                  on a.wbs_id = b.wbs_id
                                                  where b.wp_id = '$wp_id'
                                                  and b.rh_id = '$rh_id'
                                                )
                                                ")->row()->REBASELINE;
            }
            elseif($checkmember == 'no'){
                $checktask = $this->db->query("
                                                select rebaseline from (
                                                  select 'yes' as rebaseline 
                                                  from temporary_wbs a
                                                  join wbs_pool b
                                                  on a.wbs_id = b.wbs_id
                                                  where b.wp_id = '$wp_id'
                                                  and a.rh_id = '$rh_id'
                                                  UNION 
                                                  select 'no' as rebaseline 
                                                  from wbs a
                                                  join wbs_pool b
                                                  on a.wbs_id = b.wbs_id
                                                  where b.wp_id = '$wp_id'
                                                  and b.rh_id = '$rh_id'
                                                )
                                                ")->row()->REBASELINE;
            }

            //insert timesheet to temporary timesheet if member task need rebaseline approval
            if($checkmember == 'yes'){
                $this->M_timesheet->inputTimesheetTemp($data,$rh_id);
                $returndata['status'] = "success";
                $returndata['message'] = "add timesheet temporary succcess ";
            }
            //insert timesheet to temporary timesheet if member not need rebaseline but task need rebaseline approval
            elseif ($checktask == 'yes'){
                $this->M_timesheet->inputTimesheetTemp($data,$rh_id);

                $returndata['status'] = "success";
                $returndata['message'] = "add timesheet temporary succcess ";
            }
            //insert timesheet to original timesheet table because his member status and task status not need rebaseline approval
            else{
                $this->M_timesheet->inputTimesheet($data);
                $returndata['status'] = "success";
                $returndata['message'] = "add timesheet succcess ";
            }

        }
        elseif($statusProject == 'In Progress'){
            $this->M_timesheet->inputTimesheet($data);
            $returndata['status'] = "success";
            $returndata['message'] = "add timesheet succcess ";
        }
        else{
            $this->output->set_status_header(400);
            $returndata['status'] = "failed";
            $returndata['message'] = "Status project tidak dalam In Progress atau On Hold";
        }

        echo json_encode($returndata);
    }

    //confirmation timesheet(approve or decline)
    function confirmationTimesheet(){

        $approver = $this->datajson['USER_ID'];
        $timesheet_id = $_POST['ts_id'];
        $confirm_code = $_POST['confirm'];
        $project_id = $_POST['project_id'];
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;

        if($confirm_code == 1 || $confirm_code  == 0){

            $rebaseline_status = $this->db->query("
                                                   select 'yes' as rebaseline
                                                   from temporary_timesheet
                                                   where ts_id = '$timesheet_id'
                                                   and rh_id = '$rh_id'
                                                   union 
                                                   select 'no' as rebaseline
                                                   from timesheet
                                                   where ts_id = '$timesheet_id'
                                                    ")->row()->REBASELINE;

            if($rebaseline_status == null){
                $data['status'] = 'error';
                $data['message'] = 'timesheet id wrong';
                echo json_encode($data);
                die;
            }

            if($rebaseline_status == 'yes'){
                $confirmation = $this->M_timesheet->confirmTimesheetTemp($timesheet_id,$approver,$confirm_code);
                $data['message'] = 'rebaseline';
            }
            else{
                $confirmation = $this->M_timesheet->confirmTimesheet($timesheet_id,$approver,$confirm_code);
                //if timesheet confirmed ,calculation for workplan complete hours process execute
                if($confirm_code == 1){
                    $this->M_timesheet->updateProgress($timesheet_id);
                }
                $data['message'] = 'not rebaseline';
            }

            $data['status'] = $confirmation;

            echo json_encode($data);
        }
        else{
            $data['error'] = "confirmation code is incorrect ,choose only 1 for accept and 0 for deny";
            echo json_encode($data);
            die();
        }
    }

    //transform key
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