<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Timesheet extends CI_Controller {

    public $datajson = array();
    public $bu_id = [];

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
        $this->load->model('M_session');
        $this->load->model('M_timesheet');
        $this->load->model('M_data');
        error_reporting(E_ALL & ~E_NOTICE);

        //TOKEN LOGIN CHECKER
        $datauser = $this->M_session->GetDataUser();
        //    print_r($decoded_user_data);
        $this->datajson['token'] = $datauser["token"];

        if(isset($datauser["error"]))
        {
            $this->output->set_status_header($datauser["status"]);
            echo json_encode($datauser);
            die();
        }
        $decoded_user_data = array_change_key_case($datauser["data"], CASE_UPPER);
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



        /*================================================================================*/
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
                                    order by al.type asc")->result_array();
        $profile_id = $this->datajson['userdata']['PROF_ID'];
        foreach($privilege as $priv){
            $will_die = 0;
            //jika akses url ada di dalam db
            if($priv['ACCESS_URL'] == $url_dest){
                //jika akses tipe nya business
                if($priv['TYPE'] == 'BUSINESS'){
                    if($priv['PRIVILEGE'] == 'all_bu'){
                        $this->allowed_bu ="'BAS','TSC','TMS','FNB','CIB','INS','MSS','CIA','SGP','SSI','SMS'";
                        $will_die = 0;
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
                            $bu_id_all= $this->db->query("select bu_id from p_bu")->result_array();
                            foreach ($bu_id_all as $buid){
                                $directorat_bu[] = $buid['BU_ID'];
                            }
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
                            case '1':
                                if($this->datajson['userdata']['PROF_ID'] == 7){
                                    $bu_id = 'masuk';
                                }
                                else{
                                    $bu_id = 'cant';
                                }
                                break;
                            case '2':
                                if($url_dest == 'project/addproject_acion'){
                                    $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['BU']."'")->row()->BU_ID;
                                }
                                elseif ($url_dest == 'project/addproject_view'){
                                    $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
                                }
                                break;
                            case '3':
                                $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
                                break;
                            case '4':
                                $bu_id = $this->input->post('BU_ID');
                                break;
                            case '5':
                                $this->allowed_bu ="'".$this->db->query("select bu_code from p_bu where bu_id = '$user_bu'")->row()->BU_CODE."'";
                                $bu_id = 'masuk';
                                break;
                            case '6':
                                $bu_id = $_POST['bu'];
                                break;
                            case '7':
                                $bu_id = $_POST['BU_ID'];
                                break;
                            case '8' :
                                if($this->datajson['userdata']['PROF_ID'] == 3 || $this->datajson['userdata']['PROF_ID'] == 7 ){
                                    $bu_id = 'masuk';
                                }
                                break;
                        }
                        if(!((array_search($bu_id,$directorat_bu) != null|| $bu_id == 'masuk') && $bu_id != null)){
                            $will_die = 1;
                        }
                        else{
                            $will_die = 0;
                        }
                    }
                    else{
                        $will_die = 1;
                    }
                    if($will_die ==1){
                        $user_bu_name = $this->db->query("select bu_name from p_bu where bu_id = '".$this->datajson['userdata']['BU_ID']."'")->row()->BU_NAME;
                        $acces_bu_name = $this->db->query("select bu_name from p_bu where bu_id = '".$bu_id."'")->row()->BU_NAME;
                        $this->output->set_status_header(403);
                        $returndata['status'] = 'failed';
                        $returndata['message'] = "Anda tidak bisa mengakses feature yang ada di business unit ini. Business unit anda : '$user_bu_name' dan business unit yang anda akan akses : '$acces_bu_name'";
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
                            case '9':
                                $project_id_req = $_POST['PROJECT_ID'];
                                break;
                            case '10':
                                $project_id_req = $_POST['project_id'];
                                break;
                            case '11':
                                switch ($url_dest){
                                    case 'task/upload_wbs':
                                        $project_id_req = $_POST['project_id'];
                                        break;
                                    case 'task/assigntaskmemberproject':
                                        $project_id = explode(".",$_POST['WBS_ID']);
                                        $project_id_req = $project_id[0];
                                        break;
                                    case 'task/removetaskmemberproject':
                                        $project_id = explode(".",$_POST['WBS_ID']);
                                        $project_id_req = $project_id[0];
                                        break;
                                    case 'task/createtask':
                                        $project_id_req   = $this->input->post("PROJECT_ID");
                                        break;
                                    case 'task/edittaskpercent':
                                        $project_id_req=$this->input->post("PROJECT_ID");
                                        break;
                                    case 'task/edittask_action':
                                        $project_id_req= $this->input->post("project_id");
                                        break;
                                    case 'task/deletetask':
                                        $id = $_POST['wbs_id'];
                                        $project_id_req = $this->M_detail_project->getProjectTask($id);
                                        break;
                                }
                                break;
                            case '12':
                                $id = $_POST['MEMBER'];
                                $project_id_req = $this->M_detail_project->getRPProject($id);
                                break;
                            case '13':
                                $project_id_req = $this->uri->segment(3);
                                break;
                            case '14':
                                $project_id_req =$this->input->post("PROJECT_ID");
                                break;
                        }

                        if(!in_array($project_id_req,$granted_project_list)){
                            $will_die = 1;
                        }
                    }
                    else{
                        $will_die = 1;
                    }
                    if($will_die ==1){
                        $this->output->set_status_header(403);
                        $returndata['status'] = 'failed';
                        $returndata['message'] = 'Anda tidak bisa mengakses feature ini';
                        echo json_encode($returndata);
                        die;
                    }
                }
                else{
                    $will_die = 1;
                }
                if($will_die ==1){
                    $this->output->set_status_header(403);
                    $returndata['status'] = 'failed';
                    $returndata['message'] = 'Anda tidak bisa mengakses feature ini';
                    echo json_encode($returndata);
                    die;
                }
            }
        }
        /*===============================================================================*/

    }

    //for timesheet view data
    function view(){

        //select project based on user
        $date = $this->input->post('date');
        $date = ($date!="")?$date:date("Y-m-d");

        $userid = $this->datajson['userdata']['USER_ID'];
        $activity = [];
        $project = $this->db->query("SELECT distinct project_name, project_id , project_status FROM CARI_TASK WHERE PROJECT_STATUS <> 'Completed' AND USER_ID='".$userid."'")->result_array();
        $project_list = $this->db->query("SELECT DISTINCT project_id
          FROM
          (SELECT *
          FROM USER_TIMESHEET_NEW where ts_date= to_date('$date','yyyy-mm-dd')
          ORDER BY ts_date DESC)
          WHERE user_id ='".$this->datajson['userdata']['USER_ID']."'")->result_array();
        foreach($project_list as $pl){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '".$pl['PROJECT_ID']."'")->row()->RH_ID;
            $activity_list = $this->db->query("
        SELECT *
          FROM
          (SELECT *
          FROM(
        SELECT ts_id,
        substr(
            ts_id,
            1,
            instr(
                ts_id,
                '.'
            ) - 1
        ) AS wp,
        substr(
            ts_id,
            instr(
                ts_id,
                '.'
            ) + 1
        ) AS date_id,
        e.wbs_id,
        c.rp_id,
        c.user_id,
        f.user_name,
        c.project_id,
        d.project_name,
        e.wbs_name,
        subject,
        message,
        hour_total,
        ts_date,
        TO_CHAR(
            ts_date,
            'mm'
        ) AS bulan,
        TO_CHAR(
            ts_date,
            'month'
        ) AS month,
        TO_CHAR(
            ts_date,
            'YYYY'
        ) AS tahun,
        longitude,
        latitude,
        submit_date,
        is_approved,
        b.rebaseline as task_member_rebaseline,
        e.rebaseline as task_rebaseline,
        a.rebaseline as timesheet_rebaseline
        FROM
          (select wp_id,is_approved,submit_date,LATITUDE,LONGITUDE,TS_DATE,HOUR_TOTAL,MESSAGE,SUBJECT,TS_ID,'no' as rebaseline,null as rh_id 
          from timesheet 
          union 
          select wp_id,is_approved,submit_date,LATITUDE,LONGITUDE,TS_DATE,HOUR_TOTAL,MESSAGE,SUBJECT,TS_ID,'yes' as rebaseline,rh_id 
          from temporary_timesheet where rh_id = '$rh_id') a
          LEFT JOIN (
          (select wp_id,rp_id,wbs_id,'no' as rebaseline 
          from wbs_pool) 
          union 
          (select wp_id,rp_id,wbs_id,'yes' as rebaseline 
          from temporary_wbs_pool 
          where rh_id = '$rh_id')
          ) b 
          ON a.wp_id = b.wp_id
          LEFT JOIN resource_pool c ON b.rp_id = c.rp_id
          LEFT JOIN projects d ON c.project_id = d.project_id
          LEFT JOIN (select wbs_id,wbs_name,'no' as rebaseline from wbs union select wbs_id,wbs_name,'yes' as rebaseline 
          from temporary_wbs where rh_id = '$rh_id') e ON b.wbs_id = e.wbs_id
          INNER JOIN users f ON c.user_id = f.user_id) where ts_date= to_date('$date','yyyy-mm-dd') and project_id = '".$pl['PROJECT_ID']."' 
          ORDER BY ts_date DESC )
          WHERE user_id ='".$this->datajson['userdata']['USER_ID']."'")->result_array();
            foreach ($activity_list as $al){
                $activity[] = $al;
            }
        }
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
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$id'")->row()->RH_ID;

        $user_id = $this->datajson['userdata']['USER_ID'];
        $query = $this->db->query("SELECT WP_ID,PROJECT_ID,WBS_NAME,TASK_MEMBER_REBASELINE,TASK_REBASELINE 
                                  FROM
                                  (SELECT a.USER_ID, a.USER_NAME, b.RP_ID, c.PROJECT_ID, c.PROJECT_NAME, d.WP_ID, e.wbs_name, c.PROJECT_STATUS,d.rebaseline as task_member_rebaseline,e.rebaseline as task_rebaseline
                                    FROM
                                    USERS a INNER JOIN
                                    RESOURCE_POOL b ON a.USER_ID=b.USER_ID 
                                    INNER JOIN
                                    PROJECTS c ON b.PROJECT_ID=c.PROJECT_ID 
                                    inner JOIN
                                    (select wbs_id,rp_id,wp_id,'no' as rebaseline from wbs_pool union select wbs_id,rp_id,wp_id,'yes' as rebaseline from temporary_wbs_pool where rh_id = '$rh_id') d ON d.rp_id=b.rp_id
                                    inner JOIN
                                    (select wbs_id,wbs_name,'no' as rebaseline from wbs union select wbs_id,wbs_name,'yes' as rebaseline from temporary_wbs where rh_id = '$rh_id') e ON d.wbs_id=e.wbs_id)
                                  WHERE PROJECT_ID='".$id."' and USER_ID='".$user_id."'");
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
        $data['SUBMIT_DATE']= date('Y-m-d H:i:s');
        $project_id   = $_POST['PROJECT_ID'];

        //check hour total



        //check bu_id
        $wp_id = $_POST['WP_ID'];
        if($data['WP_ID'] != "" && $project_id != "")
        {
            $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;
            //check rebaseline status for task

            $statusProject = strtolower($statusProject);

            if($statusProject == 'on hold'){

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
            elseif($statusProject == 'in progress'){
                    $this->M_timesheet->inputTimesheet($data);
                    $returndata['status'] = "success";
                    $returndata['message'] = "add timesheet succcess ";
                }
            elseif ($statusProject == null || $statusProject == ""){
                    $this->output->set_status_header(400);
                    $returndata['status'] = "failed";
                    $returndata['message'] = "Harus memilih project terlebih dahuu";
                }
            else{
                    $this->output->set_status_header(400);
                    $returndata['status'] = "failed";
                    $returndata['message'] = "Status project harus in-progress atau on-hold";
        }

        }
        else{
                $this->output->set_status_header(400);
                $returndata['status'] = "failed";
                $returndata['message'] = "Pastikan anda telah memilih project atau task terlebih dahulu";
        }
        echo json_encode($returndata);
    }
    function editTimesheet(){

        if(isset($_POST['mobile'])){
            $_POST = array_change_key_case($_POST,CASE_UPPER);
        }

        $userid=$this->datajson['userdata']['USER_ID'];
        $data['TS_ID'] = $this->input->post("TS_ID");
        $data['WORK_HOUR'] = $this->input->post("HOUR");
        $data['DATE'] = $this->input->post("TS_DATE");
        $data['SUBJECT'] = $this->input->post("TS_SUBJECT");
        $data['MESSAGE'] = $this->input->post("TS_MESSAGE");
        $data['LATITUDE'] = $this->input->post("LATITUDE");
        $data['LONGITUDE'] = $this->input->post("LONGITUDE");
        $data['PROJECT_ID'] = $this->input->post("PROJECT_ID");
        $data['WP_ID'] = $this->input->post("WP_ID");
        $data['SUBMIT_DATE']= date('Y-m-d H:i:s');
        $project_id   = $_POST['PROJECT_ID'];

        $wp_id = $_POST['WP_ID'];
        //check bu_id
        if($data['TS_ID'] == ""){

            $this->output->set_status_header(400);
            $returndata['status'] = "failed";
            $returndata['message'] = "Anda harus memilih timesheet terlebih dahulu";
        }
else if($data['WP_ID'] != "" && $project_id != "")
        {
            $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;
            //check rebaseline status for task

            $statusProject = strtolower($statusProject);

            if($statusProject == 'on hold'){

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
                                                )
                                                ")->row()->REBASELINE;
                }

                //insert timesheet to temporary timesheet if member task need rebaseline approval
                if($checkmember == 'yes'){
                    $this->M_timesheet->editTimesheetTemp($data,$rh_id);
                    $returndata['status'] = "success";
                    $returndata['message'] = "edit timesheet temporary success ";
                }
                //insert timesheet to temporary timesheet if member not need rebaseline but task need rebaseline approval
                elseif ($checktask == 'yes'){
                    $this->M_timesheet->editTimesheetTemp($data,$rh_id);

                    $returndata['status'] = "success";
                    $returndata['message'] = "edit timesheet temporary success ";
                }
                //insert timesheet to original timesheet table because his member status and task status not need rebaseline approval
                else{
                    $this->M_timesheet->editTimesheet($data);
                    $returndata['status'] = "success";
                    $returndata['message'] = "edit timesheet success ";
                }

            }
            elseif($statusProject == 'in progress'){
                $this->M_timesheet->editTimesheet($data);
                $returndata['status'] = "success";
                $returndata['message'] = "edit timesheet success ";
            }
            elseif ($statusProject == null || $statusProject == ""){
                $this->output->set_status_header(400);
                $returndata['status'] = "failed";
                $returndata['message'] = "Gagal mendapatkan status project";
            }
            else{
                $this->output->set_status_header(400);
                $returndata['status'] = "failed";
                $returndata['message'] = "Status project harus in-progress atau on-hold";
            }

        }
        else{

            $this->output->set_status_header(400);
            $returndata['status'] = "failed";
            $returndata['message'] = "Harus memilih project dan task terlebih dahulu";
        }
        echo json_encode($returndata);
    }

    //confirmation timesheet(approve or decline)
    function confirmationTimesheet(){

        $approver = $this->datajson['userdata']['USER_ID'];
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
                $this->output->set_status_header(400);
                $data['status'] = 'error';
                $data['message'] = 'Timesheet tidak valid';
                echo json_encode($data);
                die;
            }
            if($rebaseline_status == 'yes'){
                $confirmation = $this->M_timesheet->confirmTimesheetTemp($timesheet_id,$approver,$confirm_code);
                $data['message'] = 'rebaseline';
            }
            else{
                $confirmation = $this->M_timesheet->confirmTimesheet($timesheet_id,$approver,$confirm_code);
                //if timesheet confirmed ,calculation for workplan complete hours process execute\

                if($confirm_code == true){
                    $this->M_timesheet->updateProgress($timesheet_id);
                }
                $data['message'] = 'not rebaseline';
            }

            $data['status'] = $confirmation;

            echo json_encode($data);
        }
        else{
            $this->output->set_status_header(400);
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