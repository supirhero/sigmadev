<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dataset extends CI_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_session');
        $this->load->model('M_register');
        $this->load->model('M_holiday');
        $this->load->model('M_project_type');


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


    function index(){
        $data['users']['user_int']=($this->M_register->tampil_int());
        $data['users']['user_ext']=($this->M_register->tampil_eks());
        $data['customers'] = json_decode(file_get_contents('http://180.250.18.227/api/index.php/mis/customer'));
        $data['partners'] = json_decode(file_get_contents('http://180.250.18.227/api/index.php/mis/vendor'));
        $data['holiday']=($this->M_holiday->selectHoliday());
        $data['project_type'] = $this->M_project_type->selectProjectType();

        echo json_encode($data);
    }
}