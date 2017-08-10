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
        //print_r($_POST);
        $date = $_POST['date'];
        $userid = $this->datajson['userdata']['USER_ID'];
        $project = $this->db->query("SELECT distinct project_name, project_id , project_status FROM CARI_TASK WHERE PROJECT_STATUS <> 'Completed' AND USER_ID='".$userid."'")->result_array();
        $activity = $this->M_timesheet->selectTimesheet_bydate($this->datajson['userdata']['USER_ID'],$date);

        $data = [];
        $data['user_project'] = $project;
        $data['user_activities'] = $activity;
        $data['holidays']=json_decode($this->M_data->get_holidays());



        echo json_encode($data);
    }

    //get task from project
    function taskList(){
        $id=$this->input->post("PROJECT_ID");
        $user_id = $this->datajson['userdata']['USER_ID'];

        $query = $this->db->query("SELECT WP_ID,WBS_NAME as TASK_NAME FROM CARI_TASK WHERE PROJECT_ID='".$id."' and USER_ID='".$user_id."'");
        //$query = $this->db->query("SELECT * FROM CARI_TASK WHERE PROJECT_ID='900418' and USER_ID='S201506017'");

        $hasil['task'] = $query->result_array();

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
        header("Access-Control-Allow-Methods: ");
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

        if($confirm_code != 0 || $confirm_code != 1){
            $data['error'] = "confirmation code is incorrect ,choose only 1 for accept and 0 for deny";
            die();
        }

        $confirmation = $this->M_timesheet->confirmTimesheet($timesheet_id,$approver,$confirm_code);

        $data['status'] = $confirmation;

        echo json_encode($data);
    }


}