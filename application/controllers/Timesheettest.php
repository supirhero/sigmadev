<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Timesheettest extends CI_Controller {

    public $datajson = array();

    function __construct()
    {
        parent::__construct();
        $this->load->model('M_session');
        $this->load->model('M_timesheet');
        $this->load->model('M_data');
        error_reporting(E_ALL & ~E_NOTICE);

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

        $query = $this->db->query("SELECT WP_ID,WBS_NAME as TASK_NAME FROM CARI_TASK WHERE PROJECT_ID='".$id."' and USER_ID='".$user_id."'");
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
        $this->M_timesheet->inputTimesheet($data);

        $returndata['status'] = "success";
        echo json_encode($returndata);
    }

    //confirmation timesheet(approve or decline)
    function confirmationTimesheet(){
        $approver = $this->datajson['USER_ID'];
        $timesheet_id = $_POST['ts_id'];
        $confirm_code = $_POST['confirm'];

        if($confirm_code == 1 || $confirm_code  == 0){
            $confirmation = $this->M_timesheet->confirmTimesheet($timesheet_id,$approver,$confirm_code);
            //if timesheet confirmed ,calculation for workplan complete hours process execute
            if($confirm_code == 1){

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