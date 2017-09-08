<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Datamaster extends CI_Controller{

    public function __construct()
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
        $this->load->model('M_register');
        $this->load->model('M_holiday');
        $this->load->model('M_user');
        $this->load->model('M_business');
        $this->load->model('M_mis');
        $this->load->model('M_project_type');
        // $this->load->library('PHPExcel');
        // $this->load->library('PHPExcel/IOFactory');

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



        if(!isset($this->datajson['userdata']['PROF_ID']))
        {
            $this->datajson['userdata']['PROF_ID'] = 7;
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
                                $bu_id = "masuk";
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
    public function getData($type,$keyword=null,$pagenum=10,$page=1){
        switch ($type) {
            case 'bu':
            $result[$type]= $this->getbu($keyword);
            break;
            case 'user':
            $result[$type]=  $this->getuser($keyword,$page,$pagenum);
            break;
            case 'customer':
            $result[$type]=   $this->getcustomer();
            break;
            case 'partner':
            $result[$type]=   $this->getpartner();
            break;
            case 'holiday':
            $result[$type]=   $this->getholiday($keyword);
            break;
            case 'project_type':
            $result[$type]=  $this->getproject_type($keyword);
            break;
            default:
                # code...
            break;
        }
        echo json_encode($result);
    }
    public function getbu($keyword=null){
        $bu=$this->M_business->buListDet($keyword);
        return $bu;
    }
    public function getuser($keyword=null,$page=1,$pagenum=10){
        $start = ($page*$pagenum)-$pagenum;
        $end = ($page*$pagenum);
        $user=$this->M_user->user_List($keyword);
        return $user;
    }
    public function getholiday($keyword=null){
        $holiday=$this->M_holiday->selectHoliday($keyword);
        return $holiday;
    }
    public function getproject_type($keyword=null){
        $data=$this->M_project_type->selectProjectType($keyword);
        return $data;
    }
    public function getcustomer(){
        $data=$this->M_mis->getcustomerMIS();
        return $data;
    }
    public function getpartner(){
        $data=$this->M_mis->getpartnerMIS();
        return $data;
    }
    public function manage($type,$action,$other=null){
        switch ($type) {
            case 'bu':
            return   $this->bu($action);
            break;
            case 'user':
            return    $this->user($action,$other);
            break;
            case 'holiday':
            return   $this->holiday($action);
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
                    case 'download':
                    $filename ="Resource Internal.xls";
                    header('Content-type: application/ms-excel');
                    header('Content-Disposition: attachment; filename='.$filename);
                    echo
                    "<table class='table table-bordered box-shadow--dp responsive' id=''>
                    <thead>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Business Unit</th>
                        <th>Last Login</th>
                        <th>Profile</th>
                        <th>Status</th>
                    </thead>
                    <tbody>";
                        $ambildata = $this->M_user->ExportDatatoExcelIn();
                        foreach ($ambildata as $frow) {
                          echo "<tr>";
                          echo"<td>".$frow->USER_ID."</td>";
                          echo"<td>".$frow->USER_NAME."</td>";
                          echo"<td>".$frow->EMAIL."</td>";
                          echo"<td>".$frow->BU_NAME."</td>";
                          echo"<td>".$frow->LAST_LOGIN."</td>";
                          echo"<td>".$frow->PROF_NAME ."</td>";
                          echo"<td>". $frow->STATUS."</td>";
                          echo "</tr>";
                      }
                      echo "</tbody></table>";
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
                    case 'download':
                    $filename ="Resource External.xls";
                    header('Content-type: application/ms-excel');
                    header('Content-Disposition: attachment; filename='.$filename);
                    echo
                    "<table class='table table-bordered box-shadow--dp responsive' id=''>
                    <thead>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Business Unit</th>
                        <th>Last Login</th>
                        <th>Profile</th>
                        <th>Status</th>
                    </thead>
                    <tbody>";
                        $ambildata = $this->M_user->ExportDatatoExcelExt();
                        foreach ($ambildata as $frow) {
                          echo "<tr>";
                          echo"<td>".$frow->USER_ID."</td>";
                          echo"<td>".$frow->USER_NAME."</td>";
                          echo"<td>".$frow->EMAIL."</td>";
                          echo"<td>".$frow->BU_NAME."</td>";
                          echo"<td>".$frow->LAST_LOGIN."</td>";
                          echo"<td>".$frow->PROF_NAME ."</td>";
                          echo"<td>". $frow->STATUS."</td>";
                          echo "</tr>";
                      }
                      echo "</tbody></table>";
                      break;
                      case 'changepassword':
                      $user_id = $this->input->post('user_id');
                      $password = md5($this->input->post('password'));

                      $this->db->query("update users set password = '$password' where user_id = '$user_id'");

                      if($this->db->affected_rows() == 1){
                        $return['status'] = 'success';
                        $return['message'] = 'Password updated';
                    }
                    else{
                        $this->output->set_status_header(400);
                        $return['status'] = 'failed';
                        $return['message'] = 'Password not updated';
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
    public function upload_users()
            {
                $config['upload_path'] = 'document_assets/temp_upload';
                $config['allowed_types'] = 'xlsx|xls|csv';
                $this->load->helper('file');
                $this->load->library('upload', $config);
               //echo $_FILES['userfile']."bob";
                //echo json_encode($_FILES['userfile']);
                if ( ! $this->upload->do_upload('userfile'))
                {
                    $data = array('error' => $this->upload->display_errors());
                    $pesan['msg']="Gagal Upload, Cek kembali lampiran anda hanya file XLS yang diizinkan";
                    $pesan['error']=$data;

                    echo json_encode($pesan);
                }
                else
                {
                    $data = array('error' => false);
                    $upload_data = $this->upload->data();
                    $this->load->library('excel_reader');
                    $this->excel_reader->setOutputEncoding('230787');
                    $file =  $upload_data['full_path'];
                    $this->excel_reader->read($file);
                    error_reporting(E_ALL ^ E_NOTICE);


            // Sheet 1
                    $data       = $this->excel_reader->sheets[0] ;
            //$baris        = $data->rowcount($sheet_index=0);
                    $coba       = $data['numRows'];
                    $dataexcel  = Array();
                    for ($i = 1; $i <= $data['numRows']; $i++) {
                        if($data['cells'][$i][1] == '')
                            break;
                        $dataexcel[$i-1]['USER_ID'] = $data['cells'][$i][1];
                        $dataexcel[$i-1]['PROF_ID'] = $data['cells'][$i][2];
                        $dataexcel[$i-1]['USER_NAME'] = $data['cells'][$i][3];
                        $dataexcel[$i-1]['EMP_CAT'] = $data['cells'][$i][4];
                        $dataexcel[$i-1]['POSITION'] = $data['cells'][$i][5];
                        $dataexcel[$i-1]['BU_ID'] = $data['cells'][$i][6];
                        $dataexcel[$i-1]['EMAIL'] = $data['cells'][$i][7];

                        $dataexcel[$i-1]['SUP_ID'] = $data['cells'][$i][8];
                //$dataexcel[$i-1]['USER_TYPE_ID'] = $data['cells'][$i][8];
                //$dataexcel[$i-1]['PHONE_NO'] = $data['cells'][$i][8];
                //$dataexcel[$i-1]['IS_ACTIVE'] = $data['cells']['0'][9];
                //$dataexcel[$i-1]['PASSWORD'] = md5($data['cells'][$i][9]);
                //$dataexcel[$i-1]['ADDRESS'] = $data['cells'][$i][11];
                //$dataexcel[$i-1]['IS_SHIFT'] = $data['cells'][$i][12];
                //$dataexcel[$i-1]['IMAGE'] = $data['cells'][$i][13];
                    }
            //delete_files($upload_data['file_path']);
                    $this->M_register->tambahuser($dataexcel);
                    for($i = 1; $i <= $data['numRows']; $i++){
                        $email =    $dataexcel[$i]['EMAIL'];
                        $name = $dataexcel[$i]['USER_NAME'];
            //$this->sendVerification($email,$name);
                    }
            //$this->session->set_flashdata("pesan","<div class=\"alert alert-success\" id=\"alert\"><i class=\"glyphicon glyphicon-ok\"></i> Data berhasil di upload,berhasil di upload </div>");
                    $baris = count($dataexcel);
                    $total = $baris-1;
                    $pesan['msg']="Data berhasil di upload ";
                    delete_files($file);
                    echo json_encode($pesan);
                }

            }
}
