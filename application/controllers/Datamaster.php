<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Datamaster extends CI_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_session');
        $this->load->model('M_register');
        $this->load->model('M_holiday');
        $this->load->model('M_user');
        $this->load->model('M_business');
        $this->load->model('M_mis');
        $this->load->model('M_project_type');


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

        //for debug only
        // $masterdata = $this->db->query("select * from users where USER_NAME = 'master'")->result_array();
        //$this->datajson['userdata']= $masterdata[0];
    }
    public function getData($type,$keyword=null){
        switch ($type) {
            case 'bu':
                $this->getbu($keyword);
                break;
            case 'user':
                $this->getuser($keyword);
                break;
            case 'customer':
                $this->getcustomer();
                break;
            case 'partner':
                $this->getpartner();
                break;
            case 'holiday':
                $this->getholiday($keyword);
                break;
            case 'project_type':
                $this->getproject_type($keyword);
                break;
            default:
                # code...
                break;
        }
    }
    public function getbu($keyword=null){
        $bu=$this->M_business->buListDet($keyword);
        echo json_encode($bu);
    }
    public function getuser($keyword=null){
        $user=$this->M_user->userList($keyword);
        echo json_encode($user);
    }
    public function getholiday($keyword=null){
        $holiday=$this->M_holiday->selectHoliday($keyword);
        echo json_encode($holiday);
    }
    public function getproject_type($keyword=null){
        $data=$this->M_project_type->selectProjectType($keyword);
        echo json_encode($data);
    }
    public function getcustomer(){
        $data=$this->M_mis->getcustomerMIS();
        echo $data;
    }
    public function getpartner(){
        $data=$this->M_mis->getpartnerMIS();
        echo $data;
    }
    public function manage($type,$action){
        switch ($type) {
            case 'bu':
                $this->bu($action);
                break;
            case 'user':
                $this->user($action);
                break;
            case 'holiday':
                $this->holiday($action);
                break;
            default:
                # code...
                break;
        }
    }
    public function bu($action){
        switch ($action) {
            case 'add':
                /*method : POST
                # Data
                ('BU_CODE');
                ('BU_PARENT_ID'); --> BU_ID yg menjadi parent BU tersebut
                ('BU_NAME');
                ('BU_ALIAS');
                ('BU_HEAD') --> user_id di table users
                */
                // check apakah BU CODE sudah digunakan
                $checkCode=$this->M_business->checkExist('BU_CODE',$this->input->post('BU_CODE'));
                // check apakah BU ALIAS sudah digunakan
                $checkAlias=$this->M_business->checkExist('BU_ALIAS',$this->input->post('BU_ALIAS'));

                if($checkCode=='code_dup'){
                    $c['status']='Error';
                    $c['msg']='Duplicate BU Code data';
                    $c['data']=null;
                }elseif($checkAlias=='alias_dup'){
                    $c['status']='Error';
                    $c['msg']='Duplicate BU Alias data';
                    $c['data']=null;
                }else{
                    $c['status']='Success';
                    $c['msg']='BU added';
                    $c['data']=$this->M_business->addNewBU();
                }
                break;
            case 'update':
                /*method : POST
                # Data
                ('BU_CODE');
                ('BU_ID');
                ('BU_NAME');
                ('BU_ALIAS');
                ('BU_HEAD') --> user_id di table users
                */
                $c['data']=$this->M_business->updateBUData();
                //check apakah data terupdate
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='Data updated';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }

                break;
            case 'changelevel':
                /*method : POST
                # Data
                ('BU_ID'); --> BU_ID yg menjadi parent BU tersebut
                ('BU_PARENT_ID');
                */
                $c['data']=$this->M_business->updateBULevel();
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='BU level updated';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }
                break;
            case 'toggleactive':
                /*method : POST
                # Data
                ('BU_ID'); --> BU_ID yg menjadi parent BU tersebut
                ('STAT'); --> 0 untuk deactive, 1 untuk active
                */
                $c['data']=$this->M_business->updateBUStatus();
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='BU status updated';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }
                break;
            default:
                # code...
                break;
        }
        echo json_encode($c);
    }
    public function user($type,$action){
        switch ($type) {
            case 'int':
                switch ($action) {
                    case 'emailactivation':
                        $type=$this->input->post('STAT');
                        $user_id=$this->input->post('USER_ID');
                        $email=$this->M_user->getEmail($user_id);
                        $c['user_id']=$user_id;
                        $c['email_to']=$email;
                        switch($type){
                            case '1':
                                $this->M_user->deleteIdentifier($email);
                                $this->M_user->createIdentifier($email);
                                $name=$this->M_user->getName($email);
                                $c['data']=$this->M_user->sendVerification($email,$name);
                                if ($c['data']) {
                                    $c['status']='Success';
                                    $c['msg_email']='Email sent, waiting user to click activation link';
                                }else{
                                    $c['status']='Error';
                                    $c['msg_email']='Cannot send email activation';
                                }
                                break;
                            case '0':
                                $name=$this->M_user->getName($email);
                                $this->M_user->deactivateUser($email);
                                $c['data']=$this->M_user->sendDeactivateInfo($email,$name);
                                if ($c['data']) {
                                    $c['status']='Success';
                                    $c['msg_email']='Email info sent';
                                }else{
                                    $c['status']='Error';
                                    $c['msg_email']='Cannot send email deactivation';
                                }
                                break;
                        }
                        break;

                    default:
                        # code...
                        break;
                }
                break;
            case 'ext':
                switch ($action) {
                    case 'emailactivation':
                        $type=$this->input->post('STAT');
                        $user_id=$this->input->post('USER_ID');
                        $email=$this->M_user->getEmail($user_id);
                        $c['user_id']=$user_id;
                        $c['email_to']=$email;
                        switch($type){
                            case '1':
                                $this->M_user->deleteIdentifier($email);
                                $this->M_user->createIdentifier($email);
                                $name=$this->M_user->getName($email);
                                $c['data']=$this->M_user->sendVerification($email,$name);
                                if ($c['data']) {
                                    $c['status']='Success';
                                    $c['msg_email']='Email sent, waiting user to click activation link';
                                }else{
                                    $c['status']='Error';
                                    $c['msg_email']='Cannot send email activation';
                                }
                                break;
                            case '0':
                                $name=$this->M_user->getName($email);
                                $this->M_user->deactivateUser($email);
                                $c['data']=$this->M_user->sendDeactivateInfo($email,$name);
                                if ($c['data']) {
                                    $c['status']='Success';
                                    $c['msg_email']='Email info sent';
                                }else{
                                    $c['status']='Error';
                                    $c['msg_email']='Cannot send email deactivation';
                                }
                                break;
                        }
                        break;
                    case 'autoactivation':
                        $user_id=$this->input->post('USER_ID'); //ambil data user_id vendor
                        $emailv=$this->M_user->getEmail($user_id);
                        $sup_id=$this->M_user->getSupID($user_id); //ambil data sup_id
                        $email=$this->M_user->getEmailSupID($sup_id); //ambil data email
                        $this->M_user->deleteIdentifier($emailv);
                        $name=$this->M_user->getName($email);
                        $namevendor=$this->M_user->getNameVendor($emailv);
                        $this->M_user->statusActive($user_id);
                        $c['data']=$this->M_user->sendVerificationManual($email,$name,$namevendor);
                        if ($c['data']) {
                            $c['status']='Success';
                            $c['msg_email']='Email info sent';
                        }else{
                            $c['status']='Error';
                            $c['msg_email']='Cannot send email deactivation';
                        }
                        break;
                    default:
                        # code...
                        break;
                }
                break;
            default:
                # code...
                break;
        }

        echo json_encode($c);
    }
    public function holiday($action){
        switch ($action) {
            case 'add':
                $id = $this->M_holiday->getMaxHoliday();
                $data['HOLIDAY_ID'] 		    = $id;
                $data['HOLIDAY'] 			      = $this->input->post("HOLIDAY");
                $data['HOLIDAY_START'] 			= "TO_DATE('".$this->input->post("HOLIDAY_START")."','yyyy-mm-dd')";
                $data['HOLIDAY_END'] 			  = "TO_DATE('".$this->input->post("HOLIDAY_END")."','yyyy-mm-dd')";
                if (isset($_POST['COLOR'])||!empty($_POST['COLOR'])) {
                    $data['COLOR'] 			      = $this->input->post("COLOR");
                }else{
                    $data['COLOR'] 			      = null;
                }
                $c['data']=$this->M_holiday->insertHoliday($data);
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='Data inserted';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }
                break;
            case 'update':
                $data['HOLIDAY_ID'] 		= $this->input->post("HOLIDAY_ID");
                $data['HOLIDAY'] 			  = $this->input->post("HOLIDAY");
                $data['HOLIDAY_START'] 	= $this->input->post("HOLIDAY_START");
                $data['HOLIDAY_END'] 		= $this->input->post("HOLIDAY_END");
                if (isset($_POST['COLOR'])||!empty($_POST['COLOR'])) {
                    $data['COLOR'] 			      = $this->input->post("COLOR");
                }else{
                    $data['COLOR'] 			      = null;
                }
                $c['data']=$this->M_holiday->editHoliday($data);
                if ($c['data']!=false) {
                    $c['status']='Success';
                    $c['msg']='Data updated';
                }else{
                    $c['status']='Error';
                    $c['msg']='No data found';
                    $c['data']=null;
                }
                break;
            case 'delete':
                $id		= $this->input->post("HOLIDAY_ID");
                $this->M_holiday->deleteHoliday($id);
                $c['status']='Success';
                $c['msg']='Data deleted';
                break;
            default:
                # code...
                break;
        }
        echo json_encode($c);
    }
    public function test(){
        $data=array(
            array('id'=>0,'parent'=>null,'level'=>null,'leaf'=>0),
            array('id'=>1,'parent'=>0,'level'=>1,'leaf'=>1),
            array('id'=>2,'parent'=>0,'level'=>1,'leaf'=>0),
            array('id'=>3,'parent'=>2,'level'=>2,'leaf'=>0),
            array('id'=>4,'parent'=>3,'level'=>3,'leaf'=>1),
            array('id'=>5,'parent'=>3,'level'=>3,'leaf'=>1),
            array('id'=>6,'parent'=>2,'level'=>2,'leaf'=>1),
            array('id'=>7,'parent'=>0,'level'=>1,'leaf'=>1)
        );
        echo json_encode($data);
        $node=1;
        $seq=1;
        $next=1;
        $lv=1;
        echo '<br>';
        foreach ($data as $d) {
            for ($i=0; $i <$d['level'] ; $i++) {
                // if ($lv==$d['level']) {
                //   $next++;
                // }
                // if ($i==$d['level']-1) {
                //   $node.='.'.$next;
                // }else{
                //   $node.='.'.$seq;
                // }
                $node.=".";
            }
            echo($d['id']).'=== '.$node.'<br>';
        }
    }
}