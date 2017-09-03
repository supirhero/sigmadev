<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Role extends CI_Controller
{

    public $datajson = array();

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
        $this->load->model('M_role');
        $this->load->model('M_session');

        //TOKEN LOGIN CHECKER
        if(isset($_GET['token'])){
            $datauser["data"] = $this->M_session->GetDataUser($_GET['token']);
            $decoded_user_data = array_change_key_case($datauser["data"], CASE_UPPER);
            //    print_r($decoded_user_data);
            $this->datajson['token'] = $_GET['token'];

        }
        elseif(isset($_SERVER['HTTP_TOKEN'])){
            $datauser["data"] = $this->M_session->GetDataUser($_SERVER['HTTP_TOKEN']);

            $decoded_user_data = array_change_key_case($datauser["data"], CASE_UPPER);
            $this->datajson['token'] = $_SERVER['HTTP_TOKEN'];
        }
        else{
            print_r($_GET);
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
            $this->output->set_status_header(400);
            $error['error']="Login error";
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
            $will_die = 0;
            //jika akses url ada di dalam db
            if($priv['ACCESS_URL'] == $url_dest){
                //jika akses tipe nya business
                if($priv['TYPE'] == 'BUSINESS'){
                    if($priv['PRIVILEGE'] == 'all_bu'){
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
                            case '1':
                                if($this->datajson['userdata']['PROF_ID'] == 7){
                                    $bu_id = 'masuk';
                                }
                                else{
                                    $bu_id = 'cant';
                                }
                                break;
                            case '2':
                                $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['BU']."'")->row()->BU_ID;
                                break;
                            case '3':
                                $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
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
                }
                else{
                    $will_die = 1;
                }

                if($will_die ==1){
                    $this->output->set_status_header(403);
                    $returndata['status'] = 'failed';
                    $returndata['message'] = 'Anda tidak bisa mengakses feature yang ada di business unit ini';
                    echo json_encode($returndata);
                    die;
                }
            }
        }
        /*===============================================================================*/

    }

    function getProfile(){
        $data['profile'] = $this->db->query("select * from profile order by prof_id asc")->result_array();

        echo json_encode($data);
    }

    function createProfile(){
        $prof_id = $this->db->query("select nvl(max(prof_id)+1,0) as id from profile")->row()->ID;
        $prof_name = $this->input->post('role_name');
        $prof_desc = $this->input->post('role_desc');

        $role[0] = $this->input->post('role_1');
        $role[1] = $this->input->post('role_2');
        $role[2] = $this->input->post('role_3');
        $role[3] = $this->input->post('role_4');
        $role[4] = $this->input->post('role_5');
        $role[5] = $this->input->post('role_6');
        $role[6] = $this->input->post('role_7');
        $role[7] = $this->input->post('role_8');
        $role[8] = $this->input->post('role_9');
        $role[9] = $this->input->post('role_10');
        $role[10] = $this->input->post('role_11');
        $role[11] = $this->input->post('role_12');
        $role[12] = $this->input->post('role_13');
        $role[13] = $this->input->post('role_14');
        $role[14] = $this->input->post('role_15');
        $role[15] = $this->input->post('role_16');
        $role[16] = $this->input->post('role_17');

        $insert_profile = [
            'PROF_ID'=>$prof_id,
            'PROF_NAME'=>$prof_name,
            'PROF_DESC'=>$prof_desc
        ];
        $this->db->insert('PROFILE',$insert_profile);
        if($this->db->affected_rows() == 1){
            $data['create_profile'] = 'success';
            $i = 0;
            foreach($role as $priv){
                if($role[$i] != null || $role[$i] != ""){
                    $add = [
                        'PRIVILEGE'=>$priv,
                        'PROFILE_ID'=>$prof_id,
                        'ACCESS_ID'=>$i+1
                    ];
                    $this->db->insert('PROFILE_ACCESS_LIST',$add);
                }
                $i++;
            }

        }

        $data['status']= 'success';
        echo json_encode($data);

    }

    function editProfile_view(){
        $prof_id = $this->input->post('profile_id');
        $data['profile_setting'] = $this->db->query("select * from profile where prof_id = '$prof_id'")->result_array();
        $data['profile_privilege'] = $this->db->query("select al.access_name,al.type,pac.privilege
                                                        from profile join profile_access_list pac
                                                        on profile.prof_id = pac.profile_id
                                                        join access_list al
                                                        on al.access_id=pac.access_id
                                                        where profile.prof_id = '".$prof_id."'")->result_array();
        echo json_encode($data);
    }

    function editProfile_action(){
        $prof_id = $this->input->post('profile_id');
        $prof_name = $this->input->post('role_name');
        $prof_desc = $this->input->post('role_desc');

        $role[0] = $this->input->post('role_1');
        $role[1] = $this->input->post('role_2');
        $role[2] = $this->input->post('role_3');
        $role[3] = $this->input->post('role_4');
        $role[4] = $this->input->post('role_5');
        $role[5] = $this->input->post('role_6');
        $role[6] = $this->input->post('role_7');
        $role[7] = $this->input->post('role_8');
        $role[8] = $this->input->post('role_9');
        $role[9] = $this->input->post('role_10');
        $role[10] = $this->input->post('role_11');
        $role[11] = $this->input->post('role_12');
        $role[12] = $this->input->post('role_13');
        $role[13] = $this->input->post('role_14');
        $role[14] = $this->input->post('role_15');

        $this->db->query("update profile set prof_name = '$prof_name',prof_desc = '$prof_desc' where prof_id = '$prof_id'");
        if($this->db->affected_rows() == 1){
            $data['change_profile'] = 'success';
        }
        $i = 0;
        foreach($role as $priv){
            if($role[$i] != null || $role[$i] != ""){
                $change = [
                    "PRIVILEGE"=>$priv
                ];
                $this->db->where('PROFILE_ID',$prof_id);
                $this->db->where('ACCESS_ID',$i+1);
                $this->db->update('PROFILE_ACCESS_LIST',$change);
            }
            $i++;
            if($this->db->affected_rows() == 1){
                $data['profile_privilege'] = 'success';
            }
        }

        $data['status']= 'success';
        echo json_encode($data);


    }

    function editRule_action(){
        $user_id = $this->input->post('user_id');
        $assigned_profile = $this->post('assigned_prof_id');

        if($this->datajson['userdata']['PROF_ID'] == 7){

            $this->db->query("update users set prof_id = '$assigned_profile' where user_id = '$user_id'");

            if($this->db->affected_rows() == 1){
                $data['status'] = 'success';
                $data['message'] = 'success ganti profile';
            }
            else{
                $this->output->set_status_header(500);
                $data['status'] = 'error';
                $data['message'] = 'Something wrong with server';
            }
        }
        else{
            $this->output->set_status_header(403);
            die;
        }

    }

    function userAccess_view(){
        $user_access['user_list'] = $this->db->query("select u.user_id , u.user_name,u.email,p.prof_name
                            from users u 
                            join profile p
                            on u.PROF_ID = p.PROF_ID")->result_array();

        $user_access['profile_list'] = $this->db->query("select prof_id,prof_name from profile")->result_array();

        echo json_encode($user_access);
    }

    function userAccess_edit(){
        $user_id = $this->input->post('user_id');
        $prof_id = $this->input->post('prof_id');

        $this->db->query("update users set prof_id = '$prof_id' where user_id = '$user_id'");

        if($this->db->affected_rows() == 1){
            echo json_encode(['status'=>'success']);
        }
        else{
            $this->output->set_status_header(500);
            echo json_encode(['status'=>'failed']);
        }
    }
}