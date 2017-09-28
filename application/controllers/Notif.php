<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Notif extends CI_Controller
{

    private $datajson = [];
    private $wp_modif = false;
    function __construct()
    {
        parent::__construct();
        $this->datajson['privilege'] = ['master_data_access'=>false,
            'manage_role_access'=>false,
            'create_project'=>false,
            'bu_access' => false,
            'bu_invite_member'=>false,
            'report_overview'=>false,
            'report_bu_directorat'=>false,
            'report_bu_teammember'=>true,
            'report_find_project'=>false,
            'edit_project'=>false,
            'timesheet_approval'=>false,
            'workplan_modification'=>false,
            'project_member'=>false,
            'upload_doc'=>false,
            'upload_issue'=>false,
            'approve_rebaseline'=>false,
            'edit_task_percent'=>false
        ];
        error_reporting(E_ALL & ~E_NOTICE);

        $this->load->model('M_notif');
        $this->load->model('M_session');
        $this->load->helper('file');

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
        if(count($decoded_user_data) > 0){
            //get user data from token
            //for login bypass ,this algorithm is not used
            //$this->datajson['userdata'] = (array)$decoded_user_data['data'];
            //this code below for login bypass
            $this->datajson['userdata'] = $decoded_user_data;
        }
        //if login fail
        else {
            $returndata['login_error'] = 'Login Failed';
            echo json_encode($returndata);
            die();
        }

    }

	    public function get(){
	    $user_id = $this->datajson['userdata']["USER_ID"];
	    $time = $this->input->post("time");
	    $list_notif = $this->M_notif->getNotif($user_id,$time);
	    $c['notif_list']=$list_notif["list"];
	    $c['notif_info']=$list_notif["info"];
	    echo json_encode($c,JSON_NUMERIC_CHECK);
    }

	    public function check(){
	    	$user_id = $this->datajson['userdata']["USER_ID"];
	    $c['unread_notif']=$this->M_notif->unreadNotif($user_id);
	    echo json_encode($c,JSON_NUMERIC_CHECK);
    }




}
