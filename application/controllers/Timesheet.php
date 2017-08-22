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
            $returndata['status'] = "failed";
            $returndata['message'] = "project status is not in progress";
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

            if($rebaseline_status == 'yes'){
                $confirmation = $this->M_timesheet->confirmTimesheetTemp($timesheet_id,$approver,$confirm_code);
            }
            else{
                $confirmation = $this->M_timesheet->confirmTimesheet($timesheet_id,$approver,$confirm_code);
                //if timesheet confirmed ,calculation for workplan complete hours process execute
                if($confirm_code == 1){
                    $this->M_timesheet->updateProgress($timesheet_id);
                }
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