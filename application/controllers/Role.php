<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Role extends CI_Controller
{

    public $datajson = array();

    function __construct()
    {
        parent::__construct();
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
    }

    function getProfile(){
        $data['profile'] = $this->db->query("select * from profile")->result_array();

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
                                                        where profile.prof_id = '".$this->datajson['userdata']['PROF_ID']."'")->result_array();
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
        $role[15] = $this->input->post('role_16');

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
        }

        $data['status']= 'success';
        echo json_encode($data);


    }
}