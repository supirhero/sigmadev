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
        $datauser = $this->M_session->GetDataUser();
        //    print_r($decoded_user_data);
        $this->datajson['token'] = $datauser["token"];

        if(isset($datauser["error"]))
        {
            $this->output->set_status_header($datauser["status"]);
            echo json_encode($datauser);
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

        $usediwo = $this->db->query("select distinct iwo_no from projects")->result_array();

         //get iwo
        @$json = file_get_contents('http://180.250.18.227/api/index.php/mis/iwo/');
        $IWO = array();
        $IWO = json_decode($json, true);

        $result_iwo1 = [];
        $result_iwo2 = [];



        foreach ($usediwo as $ui){
            $result_iwo2[] = $ui['IWO_NO'];
        }

        foreach($IWO as $iwo){
            $result_iwo1[] = $iwo['IWO_NO'];
        }



        $result_iwo = array_diff($result_iwo1,$result_iwo2);
        foreach ($result_iwo as $key => $val)
        {
           $hasil['iwo'][]= $IWO[$key];
        }
        //echo json_encode($result_iwo);

        echo json_encode($hasil);


    }

}