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

        //for debug only
        // $masterdata = $this->db->query("select * from users where USER_NAME = 'master'")->result_array();
        //$this->datajson['userdata']= $masterdata[0];
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