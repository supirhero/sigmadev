<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Iwo extends CI_Controller {

    public $datajson = array();

    public function __construct()
    {
        parent::__construct();



        $this->load->model('M_user');
        $this->load->model('M_session');
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
    }

    public function getIwo(){
        $offset = $this->uri->segment(3);
        if($offset == 0 || $offset == "" || $offset == null){
            $offset = 0;
        }

         //get iwo
        @$json = file_get_contents('http://180.250.18.227/api/index.php/mis/iwo/');
        $IWO = array();
        $IWO = json_decode($json, true);

        $IWO_VIEW['iwo'] = [];
        for($i = $offset;$i < $offset+49 ; $i++){
            array_push($IWO_VIEW['iwo'],$IWO[$i]);
        }

        echo json_encode($IWO_VIEW);

    }

}