<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Home extends CI_Controller {

    public $datajson = array();
    public function __construct()
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
        error_reporting(E_ALL  & ~E_NOTICE);

        $this->load->model('M_home');
        $this->load->model('M_project');
        $this->load->model('M_business');
        $this->load->model('M_detail_project');
        $this->load->model('M_timesheet');
        $this->load->model('M_invite');
        $this->load->model('M_issue');
        $this->load->model('M_Member_Activity');
        $this->load->model('M_data');
        $this->load->model('M_user');
        $this->load->model('M_session');

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


        /*==============================================================================*/
        /* FOR PRIVILEGE INTEGRATION */
        $user_privilege = $this->db->query("select a.access_name,b.access_id,b.privilege
                                            from access_list a join profile_access_list b
                                            on a.access_id = b.access_id
                                            where b.profile_id = '".$this->datajson['userdata']['PROF_ID']."' order by a.access_id asc ")->result_array();
        if($user_privilege[0]['PRIVILEGE'] == 'all_bu'){
            $this->datajson['privilege']['master_data_access']=true;
            $this->datajson['privilege']['manage_role_access']=true;
        }
        if($user_privilege[1]['PRIVILEGE'] == 'all_bu' || $user_privilege[2]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['create_project']=true;
        }
        if($user_privilege[2]['PRIVILEGE'] == 'all_bu' || $user_privilege[2]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['bu_access']=true;
        }
        if($user_privilege[3]['PRIVILEGE'] == 'all_bu' || $user_privilege[3]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['bu_invite_member']=true;
        }
        if($user_privilege[4]['PRIVILEGE'] == 'all_bu' || $user_privilege[4]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['report_overview']=true;
        }
        if($user_privilege[5]['PRIVILEGE'] == 'all_bu' || $user_privilege[5]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['report_bu_directorat']=true;
        }
        if($user_privilege[6]['PRIVILEGE'] == 'all_bu' || $user_privilege[5]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['report_bu_teammember']=true;
        }
        if($user_privilege[7]['PRIVILEGE'] == 'all_bu' || $user_privilege[7]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['report_find_project']=true;
        }
        if($user_privilege[8]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['edit_project']=true;
        }
        if($user_privilege[9]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['timesheet_approval']=true;
        }
        if($user_privilege[10]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['workplan_modification']=true;
        }
        if($user_privilege[11]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['project_member']=true;
        }
        if($user_privilege[12]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['upload_doc']=true;
        }
        if($user_privilege[13]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['upload_issue']=true;
        }
        if($user_privilege[14]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['edit_task_percent']=true;
        }
        if($user_privilege[15]['PRIVILEGE'] == 'all_bu' || $user_privilege[15]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['approve_rebaseline']=true;
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
                            case '16':
                                $bu_id = $this->db->query("select pbu.bu_id from projects p 
                                                           join p_bu pbu
                                                           on pbu.bu_code = p.bu_code
                                                           where p.project_id = '".$this->input->post('project_id')."'
                                                           ")->row()->BU_ID;
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
                                    case ($url_dest == 'project/rebaseline') || ( $url_dest == 'project/baseline'):
                                        $user_id = $this->datajson['userdata']['USER_ID'];
                                        $gpl = $this->db->query("select project_id from projects where pm_id ='$user_id'")->result_array();

                                        $granted_project_list = null;
                                        $granted_project_list = [];
                                        foreach ($gpl as $gg){
                                            $granted_project_list[] = $gg['PROJECT_ID'];
                                        }
                                        $project_id_req = $this->input->post("project_id");
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

    /*For Overview Home*/
    public function index(){
        $bagian_unit = $this->datajson['userdata']['BU_ID'];
        $this->datajson['userdata']['PROFILE_NAME'] = $this->db->query("select PROF_NAME from profile  where PROF_ID = ".$this->datajson['userdata']['PROF_ID'])->row()->PROF_NAME;
        $query = $this->db->query("select BU_NAME FROM P_BU WHERE BU_ID='".$bagian_unit."'")->row();
        //$this->datajson['bussines_unit'] = $query->BU_NAME;
        $this->project();
        $this->datatimesheet();
        $this->transformKeys($this->datajson);
        echo json_encode($this->datajson,JSON_NUMERIC_CHECK);
    }

    public function userdata(){

        $this->datajson['userdata']['prof_name'] = $this->db->query("select PROF_NAME from profile  where PROF_ID = ".$this->datajson['userdata']['PROF_ID'])->row()->PROF_NAME;
        $data["userdata"]=array_change_key_case($this->datajson['userdata'],CASE_LOWER);
        echo json_encode($data);
    }

    public function edit_user(){
        $nohp = $this->input->post('no_hp');
        $address = $this->input->post('address');
        //setting for upload libary
        if(isset($_FILES['image']['name']))
        {
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        }
        else{
            $extension = "";
        }
        $config['upload_path']		= 'asset/user/';
        $config['allowed_types']	= 'jpg|png|gif|jpeg';
        $config['overwrite'] = TRUE;
        $config['max_size']			= 7000000;
        $config['file_name'] = $this->datajson['userdata']['USER_ID'].".".$extension;

        $this->load->library('upload', $config);

        /*for send verification email
        $project_name=$this->M_baseline->selectProjectName($project);
        $pm_name=$this->M_baseline->selectProjectPmName($project);
        $bu_name=$this->M_baseline->selectProjectBUName($project);
        $this->sendVerificationPMO($project_name,$project,$pm_name,$bu_name,$vp_bu);
        */
        //jika gagal upload/ tidak ada file
        if ($this->upload->do_upload('image')){
            //get id rebaseline history
            $updateUser = [
                'PHONE_NO' => $nohp,
                'ADDRESS' => $address,
                'IMAGE' =>  "/asset/user/".$this->datajson['userdata']['USER_ID'].".".$extension."?".time(),
            ];
            $this->db->where('USER_ID', $this->datajson['userdata']['USER_ID']);
            $this->db->update('USERS', $updateUser);
            $data['status_code'] = '200';
            $data['status_name'] = 'success';
            $data['message'] = 'user updated';
            $data['image_error'] = "1";//kegedean filesize


        }
        // jika ada file evidence / berhasil upload
        else {
            //$data['config'] = $config;
            if(strpos(
                $this->upload->display_errors(),"Maximum"
            )){
                $data['image_error'] = "0";//kegedean filesize
            }
            elseif(strpos(
                $this->upload->display_errors(),"extension"
            ))
            {
                $data['image_error'] = "-1";//kegedean filesize

            }
            else{
                $data['image_error'] = "1";//kegedean filesize

            }

$data["error_upload"] = $this->upload->display_errors();
            $updateUser = [
                'PHONE_NO' => $nohp,
                'ADDRESS' => $address,
            ];
            $this->db->where('USER_ID', $this->datajson['userdata']['USER_ID']);
            $this->db->update('USERS', $updateUser);

            $data['status_code'] = '200';
            $data['status_name'] = 'success';
            $data['message'] = 'user updated without img';
        }


        echo json_encode($data);
    }

    //bu detail
    public function buDetail(){
        $code = $this->M_project->getBuBasedCode($_POST['bu_code'])[0]['BU_ID'];
        // check untuk filter/search
        isset($_POST['KEYWORD'])?$keyword=$_POST['KEYWORD']:$keyword=null;
        isset($_POST['STATUS'])?$status=$_POST['STATUS']:$status=null;
        isset($_POST['PROJECT_TYPE'])?$type=$_POST['PROJECT_TYPE']:$type=null;
        isset($_POST['EFFORT_TYPE'])?$effort=$_POST['EFFORT_TYPE']:$effort=null;
        // end check
        $data['project']= $this->M_project->getUsersProjectBasedBU($this->datajson['userdata']['USER_ID'],$_POST['bu_code'],$keyword,$status,$type,$effort);
        $data['member'] = $this->db->query("select user_id, user_name from users where bu_id = '$code' order by user_name ")->result_array();
        $data['nonmember'] = $this->db->query("select user_id, user_name from users where bu_id != '$code'  order by user_name ")->result_array();
        $data['bu_id'] = $code;
        $data['bu_spi']=$this->M_home->buspicpi($code)[0]->SPI;
        $data['bu_cpi']=$this->M_home->buspicpi($code)[0]->CPI;
        $data['bu_code'] = $_POST['bu_code'];

        echo json_encode($data);
    }
    /*FOR DATATIMESHEET THIS MONTH*/
    private function datatimesheet(){

        //parameter
        $tanggalnow = getdate();
        $_POST['bulan'] = $tanggalnow['mon'];
        $_POST['tahun'] = $tanggalnow['year'];
        $user_id=$this->datajson['userdata']['USER_ID'];
        if (strlen($this->input->post('bulan'))=='2'){
            $bulan = $this->input->post('bulan');
        }else {
            $bulan = '0'.$this->input->post('bulan');
        }
        $hasil['bulan']=$bulan;
        $tahun = $this->input->post('tahun');
        $y=(int)date("Y");
        $m=(int)date("m");
        // get Util data
        $total_hours=$this->M_home->getTotalHour($user_id,$bulan,$tahun);
        // get Entry Data
        $entry=$this->M_home->getEntry($user_id,$bulan,$tahun);

        //echo json_encode($entry);
        $a_date = "2009-11-23";
        $o=11;
        //if( (!isset($entry))&&(!isset($total_hours)) ){
        //$entry=0;$total_hours=0;
        //}
        //  if (($entry)&&($total_hours)){
        //  $entry=0;$total_hours=0;
        //  }
        //echo date("d-m-Y", strtotime('1-'.$o.'-2016'))."<br />";
        //echo "hu".date("t", strtotime($a_date))."hh";
        //echo $entry;
        //echo $this->countDuration('2016/11/1', '2016/11/30') /$entry*100;

        //Entry calculation
        //$hasil['e']=$entry;
        //$hasil['t']=$total_hours;
        //get date duration from begining of month
        $countduration = $this->countDuration($tahun."/".$bulan."/1", date("Y/m/d"));

        $countdurationtoday = ($countduration > 0) ? $countduration : 1;
        $countdurationlastday = $this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun));
        $countdurationlastday = ($countdurationlastday > 0) ? $countdurationlastday : 1;

        if (($bulan==$m)&& ($tahun==$y) ){
            $hasil['entry']=$entry/$countdurationtoday *100;
        }
        else{
            $hasil['entry']=$entry/$countdurationlastday *100;
        }
        //Utilization calculation
        if (($bulan==$m)&& ($tahun==$y) ){
            $hasil['utilization']=$total_hours/($countdurationtoday*8) *100;
            $hasil['c']= ($countdurationtoday*8);
        }
        else{
            $hasil['utilization']=$total_hours/($countdurationlastday*8) *100;
            $hasil['c']= ($countdurationlastday*8);

        }
        //Utilization text
        if ($hasil['utilization'] < 80)
        {
            $hasil['status_utilization']='Under';
        }
        elseif (($hasil['utilization']>=80) && ($hasil['utilization']<=100)   ){
            $hasil['status_utilization']='Optimal';
        }
        else {
            $hasil['status_utilization']='Over';
        }
        // Entry text
        if ($hasil['entry']<100)
        {
            $hasil['status']='Under';
        }
        elseif ($hasil['entry']==100) {
            $hasil['status']='Complete';
        }
        else {
            $hasil['status']='Over';
        }

        $allentry=$this->M_home->getAllEntry($user_id,$tahun);
        //$hasil['JML_ENTRY_BULANAN']=$allentry;
        $hasil['allentry'][0]=array('Month', 'Entry');
        $i=1;
        foreach ($allentry as $hasilAllentry) {
            $dateObj   = DateTime::createFromFormat('!m', $hasilAllentry['MONTH_VALUE']);
            // March
            if (($dateObj->format('m')==$m)&& ($tahun==$y) ){

                $durasi[$i]=($this->countDuration($tahun."/".$dateObj->format('m')."/1", date("Y/m/d")));
            }
            else{
                $durasi[$i]=($this->countDuration($tahun."/".$dateObj->format('m')."/1", $this->last_day($dateObj->format('m'),$tahun)));
            }
            $hasil['allentry'][$i][0]= $dateObj->format('M');
            //$dateObj->format('m');
            //test
            if($hasilAllentry['JML_ENTRY_BULANAN']>0 && $durasi[$i] >0)
                $hasil['allentry'][$i][1]=$hasilAllentry['JML_ENTRY_BULANAN']/$durasi[$i]*100;
            else
                $hasil['allentry'][$i][1]=0;


            $i++;
        }

        $allhour=$this->M_home->getAllHour($user_id,$tahun);
        $hasil['allhour'][0]=array('Month', 'Hour');
        $i=1;
        foreach ($allhour as $hasilAllhour) {

            $dateObj   = DateTime::createFromFormat('!m', $hasilAllhour['MONTH_VALUE']);
            // March
            if (($dateObj->format('m')==$m)&& ($tahun==$y) ){

                $durasihour[$i]=($this->countDuration($tahun."/".$dateObj->format('m')."/1", date("Y/m/d"))*8);
            }
            else{
                $durasihour[$i]=($this->countDuration($tahun."/".$dateObj->format('m')."/1", $this->last_day($dateObj->format('m'),$tahun))*8);
            }
            //$hasil['anjay'][$i] = $this->last_day($dateObj->format('m'),$tahun);
            $hasil['allhour'][$i][0]= $dateObj->format('M');
            if($hasilAllhour['JML_JAM_BULANAN']>0 && $durasihour[$i] >0)
                $hasil['allhour'][$i][1]=($hasilAllhour['JML_JAM_BULANAN']/$durasihour[$i])*100;
            else
                $hasil['allhour'][$i][1]=0;
            $i++;
        }
        $hasil['an']="";
        $hasil['bulan']=$bulan;
        $hasil['tahun']=$tahun;
        $hasil['a']=$entry;
        $hasil['total_hours']=$total_hours;
        $hasil['b']= $this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun));

        $this->datajson['datatimesheet'] = $hasil;



    }

    private function countDuration($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = $end->diff($start);
        $days = $interval->days;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        $holidays = $this->getHolidays();
        foreach ($period as $dt) {
            $curr = $dt->format('D');
            if (in_array($dt->format('Y-m-d'), $holidays)) {
                $days--;
            }
            if ($curr == 'Sat' || $curr == 'Sun') {
                $days--;
            }
        }
        return $days;
    }

    private function last_day($month = '', $year = '')
    {
        if (empty($month))
        {
            $month = date('m');
        }

        if (empty($year))
        {
            $year = date('Y');
        }

        $result = strtotime("{$year}-{$month}-01");
        $result = strtotime('-1 second', strtotime('+1 month', $result));

        return date('Y/m/d', $result);
    }

    private function getHolidays() {
        $var = $this->db->query("select to_char(HOLIDAY_START,'YYYY-MM-DD') as H_START, to_char(HOLIDAY_END,'YYYY-MM-DD') as H_END  from p_holiday where HOLIDAY_START is not null")->result_array();

        $w = array();
        foreach ($var as $v) {
            $x = $this->dateRange($v['H_START'], $v['H_END']);
            $w = array_merge($w, $x);
            $w = array_unique($w);
        }
        //print_r($w);
        return $w;
    }

    private function dateRange($first, $last, $step = '+1 day', $output_format = 'Y-m-d') {

        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {
            if (date("D", $current) != "Sun" && date("D", $current) != "Sat") {
                $dates[] = date($output_format, $current);
            }
            $current = strtotime($step, $current);
        }

        return $dates;
    }

    /*For Project Workplan*/
    public function p_workplan(){
        $id = $this->uri->segment(3);
        $returndata = $this->M_detail_project->selectWBS($id);
        echo json_encode($returndata);
    }

    /*FOR ASSIGNMENT*/
    public function myassignment(){
        $user_id = $this->datajson['userdata']['USER_ID'];
        $data=array();

        $data['assignment']=($this->M_home->assignmentView($user_id));
        $this->transformKeys($data);
        print_r(json_encode($data));
    }

    /*For Activities*/
    public function myactivities(){

        $user_id = $this->datajson['userdata']['USER_ID'];
        $data=array();

        //$data['header']=($this->load->view('v_header'));
        //$data['float_button']=($this->load->view('v_floating_button'));
        //$data['nav']=($this->load->view('v_nav1'));
        //$data['assignment']=($this->M_home->assignmentView($user_id));
        //$data['pr_list']=$this->M_home->assignmentProject($user_id);

        $year = $this->input->post('tahun')!= ""?$this->input->post('tahun'):date('Y');
        $bulan = $this->input->post('bulan')!= ""?$this->input->post('bulan'):date('m');
        $month = date("M", mktime(0, 0, 0, $bulan, 10));
        $data['activity_Timesheet']=($this->M_timesheet->selectTimesheet_bymonth($user_id,$month,$year));
        //$data['task_user']=($this->M_home->assignmentView($user_id));

        //$this->load->view('v_home_activity', $data);
        //$data['footer']=($this->load->view('v_footer2'));
        $this->transformKeys($data);
        print_r(json_encode($data));
    }


    /*For  Timesheet*/
    public function timesheet_old()
    {
        //$data=array();
        //$data['holidays']=$this->M_data->get_holidays();
        //$data['holidays']=json_decode($data['holidays'],true);
        //$data['header']=($this->load->view('v_header'));
        //$data['float_button']=($this->load->view('v_floating_button'));
        //$data['nav']=($this->load->view('v_nav1'));
        //$data['project'] = $this->db->query("SELECT distinct project_name, project_id , project_status FROM CARI_TASK WHERE PROJECT_STATUS <> 'Completed' AND USER_ID='".$user_id."'");
        //$data['assignment']=($this->M_home->assignmentView($user_id));
        //$data['pr_list']=$this->M_home->assignmentProject($user_id);
        //$data['tampil_Timesheet']=($this->M_timesheet->selectTimesheet($user_id));
        //$data['task_user']=($this->M_home->assignmentView($user_id));

        //$this->load->view('v_home_timesheet', $data);
        //$data['footer']=($this->load->view('v_footer2'));
    }
    public function timesheet($date=null){
        $user_id = $this->datajson['userdata']['USER_ID'];
        if($date == NULL)
            $date = date("Y-m-d", strtotime("today"));
        //  $date = date("d M Y", strtotime($date));

        $data=array();
        $holidays=$this->M_data->get_holidays();
        $holidays=array_values(json_decode($holidays,true));
        foreach ($holidays as $key)
        {
            $holyday[]=$key["HOLIDAY_DATE"];
        }
        $day[]= date('Y-m-d', strtotime($date.' Monday this week'));
        $day[]= date('Y-m-d', strtotime($date.' Tuesday this week'));
        $day[]= date('Y-m-d', strtotime($date.' Wednesday this week'));
        $day[]= date('Y-m-d', strtotime($date.' Thursday this week'));
        $day[]= date('Y-m-d', strtotime($date.' Friday this week'));
        $day[]= date('Y-m-d', strtotime($date.' Saturday this week'));
        $day[]= date('Y-m-d', strtotime($date.' Sunday this week'));
        for ($i=0; $i<7; $i++)
        {
            if (in_array($day[$i], $holyday)) {
                $hour = $this->M_timesheet->Timesheet_bydate($user_id,$day[$i]);
                $hour = ($hour == NULL) ? 0 : $hour;
                $data["weekdays"][$i]=array(
                    "day"=>$day[$i],
                    "holiday"=>true,
                    "work_hour"=>$hour
                );
            }
            else{
                $hour = $this->M_timesheet->Timesheet_bydate($user_id,$day[$i]);
                $hour = ($hour == NULL) ? 0 : $hour;

                $data["weekdays"][$i]=array(
                    "day"=>$day[$i],
                    "holiday"=>false,
                    "work_hour"=>$hour
                );
                $day[$day[$i]];
            }
        }

        // $data['holiday']=$holidays;
        //$data['tampil_Timesheet']=($this->M_timesheet->selectTimesheet_bydate($user_id,$date));
        $data['tampil_Timesheet']=($this->M_timesheet->Timesheet_bydate($user_id,$date));
        echo json_encode($data);
    }
    /*For add Timesheet*/
    public function addtimesheet(){
        $user_id = $this->datajson['userdata']['USER_ID'];

        $data['SUBJECT'] 		= $this->input->post("SUBJECT");
        $data['MESSAGE'] 		= $this->input->post("MESSAGE");
        $data['TS_DATE'] 		= $this->input->post("TS_DATE");
        $data['HOUR_TOTAL'] 			= $this->input->post("HOUR_TOTAL");
        $data['WP_ID']			 	= $user_id;

        if(insertTimesheet($data))
        {
            $returnmessage['title'] = "Success";
            $returnmessage['message'] = "berhasil tambah timesheet";
        }else
        {
            $returnmessage['title'] = "Fail";
            $returnmessage['message'] = "Gagal ditambahkan";        }

        print_r(json_encode($returnmessage));
    }

    public function projectactivities(){
        $project_id = $this->uri->segment(3);
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;

        $data['project_activities'] =  $this->db->query("SELECT *
                                FROM (
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
(select wp_id,is_approved,submit_date,LATITUDE,LONGITUDE,TS_DATE,HOUR_TOTAL,MESSAGE,SUBJECT,TS_ID,'no' as rebaseline from timesheet union select wp_id,is_approved,submit_date,LATITUDE,LONGITUDE,TS_DATE,HOUR_TOTAL,MESSAGE,SUBJECT,TS_ID,'yes' as rebaseline from temporary_timesheet where rh_id = '$rh_id') a
        LEFT JOIN (select wp_id,rp_id,wbs_id,'no' as rebaseline from wbs_pool union select wp_id,rp_id,wbs_id,'yes' as rebaseline from temporary_wbs_pool where rh_id = '$rh_id') b ON a.wp_id = b.wp_id
        LEFT JOIN resource_pool c ON b.rp_id = c.rp_id
        LEFT JOIN projects d ON c.project_id = d.project_id
        LEFT JOIN (select wbs_id,wbs_name,'no' as rebaseline from wbs 
        where wbs_id not in(
        select wbs_id
        from temporary_wbs where rh_id = '$rh_id'
        and project_id = '$project_id'
        )
        union select wbs_id,wbs_name,'yes' as rebaseline from temporary_wbs where rh_id = '$rh_id') e ON b.wbs_id = e.wbs_id
        INNER JOIN users f ON c.user_id = f.user_id
                                )
                                WHERE project_id = '".$project_id."'
                                ORDER BY ts_date DESC")->result_array();

        $this->transformKeys($data);
        print_r(json_encode($data));
    }

    /*FOR PROJECT LIST*/
    private function project($type="normal"){
        $prof = $this->datajson['userdata']['PROF_ID'];
        $id = $this->datajson['userdata']['USER_ID'];
        isset($_POST['page'])?$page=$_POST['PAGE']:$page=1;

        switch ($type) {
        case "normal":
          // no filter no search
          $projecttemp = $this->M_project->getUsersProject($id,$page,null,null,null,null,true);
          break;
        case "filter":
          // filter
          isset($_POST['STATUS'])?$status=$_POST['STATUS']:$status=null;
          isset($_POST['PROJECT_TYPE'])?$type=$_POST['PROJECT_TYPE']:$type=null;
          isset($_POST['EFFORT_TYPE'])?$effort=$_POST['EFFORT_TYPE']:$effort=null;
          $projecttemp = $this->M_project->getUsersProject($id,$page,null,$status,$type,$effort);
          break;
        case "search":
          // search
          isset($_POST['KEYWORD'])?$keyword=$_POST['KEYWORD']:$keyword=null;
          $projecttemp = $this->M_project->getUsersProject($id,$page,$keyword);
          break;
      }

        for($iter = 0 ; $iter < count($projecttemp) ; $iter ++){
            if($projecttemp[$iter]['PROJECT_COMPLETE'] == null){
                $projecttemp[$iter]['PROJECT_COMPLETE'] = 0;
            }
        }

        for($i = 0 ; $i < count($projecttemp) ; $i++){
            if(substr($projecttemp[$i]['PROJECT_COMPLETE'],0,1 ) == '.'){
                if(strlen($projecttemp[$i]['PROJECT_COMPLETE']) == 3){
                    $projecttemp[$i]['PROJECT_COMPLETE'] = str_pad($projecttemp[$i]['PROJECT_COMPLETE'],4,'0',STR_PAD_LEFT);
                }
                else{
                    $projecttemp[$i]['PROJECT_COMPLETE'] = str_pad($projecttemp[$i]['PROJECT_COMPLETE'],3,'0',STR_PAD_LEFT);
                }

            }
        }

        $projecttempfix=[];

        $bu_name = [];
        foreach ($projecttemp as $data){
            array_push($bu_name,$data['BU_NAME']);
        }
        $bu_name = array_unique($bu_name);
        //search bu code
        $bu_with_code = $this->M_project->searchBuCode($bu_name);
        foreach ($bu_with_code as $data){
            $index_array = count($projecttempfix);
            $projecttempfix[$index_array]['BU_NAME'] = $data['bu_name'];
            $projecttempfix[$index_array]['BU_CODE'] = $data['bu_code'];
            $projecttempfix[$index_array]['PROJECT_LIST']= [];
            for($i = 0 ; $i < count($projecttemp) ; $i++){
                if($projecttemp[$i]['BU_NAME'] == $data['bu_name']){
                    array_push($projecttempfix[$index_array]['PROJECT_LIST'],$projecttemp[$i]);
                }
            }
        }

        if(count($projecttemp) == 0){
            $user_bu = $this->datajson['userdata']['BU_ID'];
            $projecttempfix[0]['bu_code'] = $this->db->query("select bu_code from p_bu where bu_id = '$user_bu'")->row()->BU_CODE;
            $projecttempfix[0]['bu_name'] = $this->db->query("select bu_name from p_bu where bu_id = '$user_bu'")->row()->BU_NAME;
            $projecttempfix[0]['project'] = [];
        }

        $this->datajson['project'] = $projecttempfix;
        //$id_bu = $this->session->userdata('BU_ID');
        //$this->datajson['tampil_Timesheet']=($this->M_timesheet->selectTimesheet2($id_bu));
    }

    /*FOR DETAIL PROJECT*/
    public function detailproject(){
        $data['error'] = ' ';
        $data['message'] = ' ';
        $id=$this->uri->segment(3);
        /*$data['allbu']=$this->M_detail_project->getAllBU();
        $data['files'] = $this->M_detail_project->getAllFile($this->uri->segment(3));
//$data['progress']=$this->M_detail_project->projectProgress($this->uri->segment(3));


        $data['history']=$this->M_detail_project->getHistory($id);
        $data['parent_id']= $this->M_detail_project->getParentID($id);
        if(empty($id)){
            redirect('Home');
        }
//echo($id);

        $data['project']=$this->M_detail_project->getProjectAvailablity($id);
        if(empty($data['project'])){
            redirect('Home');
        }
        $data['project_name']=$this->M_detail_project->getProjectName($id);

        $data['AllProject']=$this->M_detail_project->getAllDataProject($id);
        $data['AllProject2']=$this->M_detail_project->getAllDataProject2($id);
        */
        $data['dataProject']=$this->M_detail_project->getDataProject($id);
        //$data['dataproject2']=$this->M_detail_project->getDataProject2($id);
        /*$data['parent_id']= $this->M_detail_project->getParentID($id);
        $data['tampil_issue']=($this->M_issue->selectIssue($id));
        $data['tampil_Activity']=($this->M_Member_Activity->selectTimesheet($id));
//echo($data['AllProject']);
//$iwo=$this->M_detail_project->getProjectIWO($id);
        */
        $data['project_detail']=$this->db->query("select iwo_no as iwo, category as effort_type, project_type, project_name,bu_name as bu_owner, project_desc as description, project_status,pm_id,user_name as pm_name 
        from projects join p_bu on projects.bu_code = p_bu.bu_code 
        join p_project_category ppc on projects.type_of_effort=ppc.id
        left join users pm on pm.user_id=projects.pm_id
        where project_id = '$id'")->row_array();
        /*$data['tampil_DETAIL']=$this->M_detail_project->selectWBS($this->uri->segment(3));
        $data['business_unit_name']=$this->M_invite->getAllBUName();
        //$self_bu=$this->session->userdata('BU_ID');
        $data['tampil_project_id']=$this->uri->segment(3);
        $bu = $this->uri->segment(3);
        $GetBUCodeProject = $this->M_detail_project->GetBUCodeProject($bu);
        $data['bu_code'] = $this->M_detail_project->getBUCode($GetBUCodeProject);
// Gantt
        $list=$this->M_project->getWBS($id);
        $wbs=NULL;

        */
        $data['pv']=$this->M_detail_project->getPV($id);
        $data['ev']=$this->M_detail_project->getEV($id);
        if(is_null($this->M_detail_project->getAC($id))){
            $data['ac']=0;
        }else{
            $data['ac']=$this->M_detail_project->getAC($id);
        }
        if($data['ac']!=0){
            $data['cpi']=round($data['ev']/$data['ac'],2);
        }else{
            $data['cpi']="Unable to count CPI";
        }
        if($data['pv']!=0){
            $data['spi']=round($data['ev']/$data['pv'],2);
        }else{
            $data['spi']="Unable to count SPI";
        }
//$data['spi']=round($data['ev']/$data['ac'],2);
/// end here
        /*
        foreach($list as $l){
            $wbs[]=array('text'=>$l['TEXT'],'id'=>$l['ID'],'parent'=>$l['PARENT'],'start_date'=>date("Y-m-d",strtotime($l['START_DATE'])),'duration'=>$l['DURATION'],'progress'=>$l['PROGRESS']);
        }
        */
        //$data['WBS']=$wbs;
        //Project Detail
        $this->datajson['overview']= $data['project_detail'];

        //Project Workplan Status
        $this->datajson['project_workplan_status']['project_status'] = $this->datajson["project_detail"]["project_detail"]["PROJECT_STATUS"];
        $this->datajson['project_workplan_status']['task'] = $this->db->query("select * from cari_task where project_id = ".$this->uri->segment(3))->result_array();

        foreach ($this->datajson['project_workplan_status']['task'] as $key=>$value){
            $this->transformKeys($this->datajson['project_workplan_status']['task'][$key]);
        }

        //Project Performance Index
        $this->datajson['project_performance_index']['pv'] = $this->datajson['project_detail']['pv'];
        $this->datajson['project_performance_index']['ev'] = $this->datajson['project_detail']['ev'];
        $this->datajson['project_performance_index']['ac'] = $this->datajson['project_detail']['ac'];
        $this->datajson['project_performance_index']['cpi'] = $this->datajson['project_detail']['cpi'];
        $this->datajson['project_performance_index']['spi'] = $this->datajson['project_detail']['spi'];

        //Project Team
        $this->datajson['project_team'] = $this->db->query("SELECT users.user_id,users.user_name,users.email,profile.prof_name,RESOURCE_POOL.RP_ID FROM RESOURCE_POOL
                                                             join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
                                                             join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
                                                             WHERE PROJECT_ID='".$this->uri->segment(3)."'")->result_array();
        foreach ($this->datajson['project_team'] as $key=>$value){
            $this->transformKeys($this->datajson['project_team'][$key]);
        }
        unset($this->datajson["project_detail"]);
        unset($this->datajson["userdata"]);

        $this->transformKeys($this->datajson);
        print_r(json_encode($this->datajson));
///

    }

    /*FOR PROJECT TEAM MEMBER*/
    public function p_teammember(){
        $projectid = $this->uri->segment(3);

        $this->datajson['project_member'] = $this->M_home->p_teammember($projectid);
        foreach ($this->datajson['project_member'] as $key=>$value){
            $this->transformKeys($this->datajson['project_member'][$key]);
        }

        $this->transformKeys($this->datajson);
        print_r(json_encode($this->datajson));


    }

    /*For Project Doc*/
    public function projectdoc(){
        $projectid = $this->uri->segment(3);

        $this->datajson['project_doc_list'] = $this->db->query("select a.*,b.user_name from (
                                                                  select project_id,doc_id,doc_name,url,to_char(date_upload,'DD-MON-YYYY HH:MI AM') as date_upload,upload_by,doc_desc,'document' as jenis from project_doc
                                                                  UNION 
                                                                  select project_id,0 as doc_id, evidence as doc_name,evidence as url,to_char(submit_date,'DD-MON-YYYY HH:MI AM') as date_upload ,request_by as upload_by,reason as doc_desc,'rebaseline' as jenis from rebaseline_history
                                                                ) a
                                                                join users b
                                                                on a.upload_by = b.user_id where project_id = $projectid")->result_array();
        foreach ($this->datajson['project_doc_list'] as $key=>$value){
            $this->transformKeys($this->datajson['project_doc_list'][$key]);
        }

        $this->transformKeys($this->datajson);
        print_r(json_encode($this->datajson));
    }
    /*For Project Doc*/
    public function deleteprojectdoc(){
        if(!isset($_POST["doc_id"]))
        {
            $this->output->set_status_header(402);
            $result["status"] = "failed";
            $result["message"] = "Doc ID kosong";  
        }
        else if($this->input->post('doc_id') != 0)
        {
            $this->M_detail_project->deleteDoc($this->input->post('doc_id'));
            $result["status"] = "success";
        }
        else{
            $this->output->set_status_header(402);
            $result["status"] = "failed";
            $result["message"] = "Document rebaseline tidak bisa di delete";
        }
       
           

        print_r(json_encode($result));
    }

    /*Issue Manajement*/
    public function projectissue(){

        $projectid = $this->uri->segment(3);

        /*$this->datajson['project_issue_list'] = $this->M_home->projectissuelist($projectid);*/
        $this->datajson['project_issue_list'] = $this->db->query("select issue_id,users.user_id as reported_by,
                            user_name,to_char(DATE_ISSUE,'DD-MON-YYYY HH:MI AM') as date_issue,note,evidence,priority,status
                            from manage_issue
                            join users on users.user_id = manage_issue.user_id
                            where project_id = '$projectid'")->result_array();

        $this->transformKeys($this->datajson);
        print_r(json_encode($this->datajson));
    }

    public function addissue(){

        $returnmessage = array();

        $config['upload_path']		= 'document_assets/issue/';
        $config['allowed_types']	= 'zip|k|docs|docx|xls|pdf|xlsx|jpg|jpeg|png';
        $config['max_size']			= 5020;
        $config['max_width']		= 1024;
        $config['max_height']		= 768;
        //$config['file_name']		= $nm;
        $this->load->library('upload', $config);
        if (! $this->upload->do_upload('file_upload')){
            $data['upload_data']= $this->upload->data();
            $id = $this->M_issue->getMaxIssue();
            $data['ISSUE_ID'] 			= $id;
            $data['USER_ID'] 			= $this->datajson['userdata']['USER_ID'];
            $data['PRIORITY'] 		    = $this->input->post("PRIORITY");
            $data['STATUS'] 			= "On Progress";
            $data['SUBJECT'] 			= $this->input->post("SUBJECT");
            $data['NOTE']			 	= $this->input->post("MESSAGE");
            $data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");
            //$data['EVIDENCE'] 			= $this->upload->data('file_name');

            if($data['PRIORITY']=='High'){
                $id = $this->M_issue->getMaxIssue();
                $data['ISSUE_ID'] 			= $id;
                $data['USER_ID'] 			= $this->datajson['userdata']['USER_ID'];
                $USER_ID		 			= $this->datajson['userdata']['USER_ID'];
                $data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");
                $data['PRIORITY'] 			= $this->input->post("PRIORITY");
                $data['STATUS'] 			= "On Progress";
                $data['SUBJECT'] 			= $this->input->post("SUBJECT");
                $data['NOTE']			 	= $this->input->post("MESSAGE");

                $userNamePM					= $this->M_issue->getNamePM($USER_ID);
                $project_name				= $this->M_issue->getProjectName($data);
                $bu							= $this->M_issue->getBUVP($USER_ID);
                $USER_VP					= $this->M_issue->getUserIDVP($bu);
                $userNameVP					= $this->M_issue->getUserNameVP($USER_VP);
                $email_vp					= $this->M_issue->getEmailVP($USER_VP);

                $this->M_issue->insertIssueHigh($data);
                $id_det= $this->M_issue->getMaxDetIssue();
                $this->M_issue->insertDetIssue3High($data,$id_det);
                //$this->sendVerification($USER_ID,$userNamePM,$bu,$USER_VP,$userNameVP,$email_vp,$data,$project_name);
                //redirect('/Detail_Project/view/'.$data['PROJECT_ID'].'#tab6');
            }
            else{
                $this->M_issue->insertIssue($data);
                $id_det= $this->M_issue->getMaxDetIssue();
                $this->M_issue->insertDetIssue3($data,$id_det);
            }
            //redirect('/Detail_Project/view/'.$data['PROJECT_ID'].'#tab6');
            $returnmessage['error_message']= $this->upload->display_errors();
            $returnmessage['title'] = "Success";
            $returnmessage['message'] = "berhasil tambah issue ,tetapi gagal upload foto";



        }else{
            $data['upload_data']= $this->upload->data();
            $id = $this->M_issue->getMaxIssue();
            $data['ISSUE_ID'] 			= $id;
            $data['USER_ID'] 			= $this->datajson['userdata']['USER_ID'];
            $data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");
            $data['PRIORITY'] 			= $this->input->post("PRIORITY");
            $data['STATUS'] 			= "On Progress";
            $data['SUBJECT'] 			= $this->input->post("SUBJECT");
            $data['NOTE']			 	= $this->input->post("MESSAGE");
            $data['EVIDENCE'] 			= $this->upload->data('file_name');

            if($data['PRIORITY']=='High'){
                $USER_ID		 			= $this->datajson['userdata']['USER_ID'];
                $data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");
                $data['PRIORITY'] 			= $this->input->post("PRIORITY");
                $data['STATUS'] 			= "On Progress";
                $data['SUBJECT'] 			= $this->input->post("SUBJECT");
                $data['NOTE']			 	= $this->input->post("MESSAGE");

                $userNamePM					= $this->M_issue->getNamePM($USER_ID);
                $project_name				= $this->M_issue->getProjectName($data);
                $bu							= $this->M_issue->getBUVP($USER_ID);
                $USER_VP					= $this->M_issue->getUserIDVP($bu);
                $userNameVP					= $this->M_issue->getUserNameVP($USER_VP);
                $email_vp					= $this->M_issue->getEmailVP($USER_VP);


                $this->M_issue->insertIssue2($data);
                $id_det= $this->M_issue->getMaxDetIssue();
                $this->M_issue->insertDetIssue2($data,$id_det);
                //$this->sendVerification($USER_ID,$userNamePM,$bu,$USER_VP,$userNameVP,$email_vp,$data,$project_name);
                //redirect('/Detail_Project/view/'.$data['PROJECT_ID'].'#tab6');
                //echo $USER_ID;
                //echo $userNamePM;
                //	echo $bu;
                //	echo $USER_VP
                //	echo $userNameVP;
                //	echo $email_vp;
                //	echo $data;
            }
            else{
                $this->M_issue->insertIssue2($data);
                $id_det= $this->M_issue->getMaxDetIssue();
                $this->M_issue->insertDetIssue2($data,$id_det);
            }
            //redirect('/Detail_Project/view/'.$data['PROJECT_ID'].'#tab6');
            $returnmessage['title'] = "success";
            $returnmessage['message'] = "berhasil tambah issue, berhasil upload foto";
        }

        print_r(json_encode($returnmessage));
    }

    /*Upload Document*/
    public function documentupload(){

        $projectid = $this->uri->segment(3);

        $config['upload_path']          = 'document_assets/document';
        $config['allowed_types']        = 'zip|doc|docs|docx|xls|pdf|xlsx|jpg|jpeg|png';
        $config['max_size']             = 5020;
        $config['remove_spaces']        = true;
        $config['overwrite']            = false;

        $this->load->library('upload', $config);

        if ( !$this->upload->do_upload('document'))
        {
            $error['error_display'] = $this->upload->display_errors();
            $error['title']='error';
            $error['message'] = 'gagal upload dokumen';
            print_r(json_encode($error));
        }
        else{
            $data = array('upload_data' => $this->upload->data());
            $newid = $this->db->query("select max(DOC_ID) as id from project_doc")->row();
            $insert = array(
                'DOC_ID' => intval($newid->ID)+1,
                'PROJECT_ID' => $projectid,
                'DOC_NAME' => $data['upload_data']['file_name'],
                'URL' => $data['upload_data']['file_name'],
             //   'DATE_UPLOAD' => "To_date('".date("d-m-Y H:i:s")."', 'DD-MM-YYYY HH24:MI:SS')",
                'UPLOAD_BY' => $this->datajson['userdata']['USER_ID'],
                'DOC_DESC' => $this->input->post('desc')
            );
            $this->db->set('DATE_UPLOAD',"to_timestamp('".date("d-m-Y H:i:s")."','DD-MM-YYYY HH24:MI:SS')",false);

            $this->db->insert('PROJECT_DOC', $insert);

            $message['title']='success';
            $message['message'] = 'Berhasil upload dokumen';
            print_r(json_encode($message));
        }
    }

    private function sendVerification($USER_ID,$userNamePM,$bu,$USER_VP,$userNameVP,$email_vp,$data,$project_name){
        $this->load->library('email');
        $config['protocol']='smtp';
        $config['smtp_host']='smtp.sigma.co.id';
        $config['smtp_user']=SMTP_AUTH_USR;
        $config['smtp_pass']=SMTP_AUTH_PWD;
        $config['smtp_port']='587';
        $config['smtp_timeout']='100';
        $config['charset']    = 'utf-8';
        $config['newline']    = "\r\n";
        $config['mailtype'] = 'html';
        $config['validation'] = TRUE;
        $this->email->initialize($config);
        $this->email->from($email_vp, 'Project & Resources Development System');
        //  $this->email->to($email_vp);
        $this->email->cc($email_vp);
        //$this->email->bcc('pmo@sigma.co.id');
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject(' High Issue Project Verification');
        $this->email->message("<!DOCTYPE html>
    <html>
    <head>
    <meta name='viewport' content='width=device-width' />
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
    <title>Account Activation</title>

    <style>
    /* -------------------------------------
    GLOBAL
    ------------------------------------- */
    * {
      margin:0;
      padding:0;
    }
    * { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; }

    img {
      max-width: 100%;
    }
    .collapse {
      margin:0;
      padding:0;
    }
    body {
      -webkit-font-smoothing:antialiased;
      -webkit-text-size-adjust:none;
      width: 100%!important;
      height: 100%;
    }


    /* -------------------------------------
    ELEMENTS
    ------------------------------------- */
    a { color: #2BA6CB;}

    .btn {
      text-decoration:none;
      color:#FFF;
      background-color: #1da1db;
      width:80%;
      padding:15px 10%;
      font-weight:bold;
      text-align:center;
      cursor:pointer;
      display:inline-block;
      border-radius: 5px;
      box-shadow: 3px 3px 3px 1px #EBEBEB;
    }

    p.callout {
      padding:15px;
      text-align:center;
      background-color:#ECF8FF;
      margin-bottom: 15px;
    }
    .callout a {
      font-weight:bold;
      color: #2BA6CB;
    }

    .column table { width:100%;}
    .column {
      width: 300px;
      float:left;
    }
    .column tr td { padding: 15px; }
    .column-wrap {
      padding:0!important;
      margin:0 auto;
      max-width:600px!important;
    }
    .columns .column {
      width: 280px;
      min-width: 279px;
      float:left;
    }
    table.columns, table.column, .columns .column tr, .columns .column td {
      padding:0;
      margin:0;
      border:0;
      border-collapse:collapse;
    }

    /* -------------------------------------
    HEADER
    ------------------------------------- */
    table.head-wrap { width: 100%;}

    .header.container table td.logo { padding: 15px; }
    .header.container table td.label { padding: 15px; padding-left:0px;}


    /* -------------------------------------
    BODY
    ------------------------------------- */
    table.body-wrap { width: 100%;}


    /* -------------------------------------
    FOOTER
    ------------------------------------- */
    table.footer-wrap { width: 100%;	clear:both!important;
    }
    .footer-wrap .container td.content  p { border-top: 1px solid rgb(215,215,215); padding-top:15px;}
    .footer-wrap .container td.content p {
      font-size:10px;
      font-weight: bold;

    }


    /* -------------------------------------
    TYPOGRAPHY
    ------------------------------------- */
    h1,h2,h3,h4,h5,h6 {
      font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; line-height: 1.1; margin-bottom:15px; color:#000;
    }
    h1 small, h2 small, h3 small, h4 small, h5 small, h6 small { font-size: 60%; color: #6f6f6f; line-height: 0; text-transform: none; }

    h1 { font-weight:200; font-size: 44px;}
    h2 { font-weight:200; font-size: 37px;}
    h3 { font-weight:500; font-size: 27px;}
    h4 { font-weight:500; font-size: 23px;}
    h5 { font-weight:900; font-size: 17px;}
    h6 { font-weight:900; font-size: 14px; text-transform: uppercase; color:#444;}

    .collapse { margin:0!important;}

    p, ul {
      margin-bottom: 10px;
      font-weight: normal;
      font-size:14px;
      line-height:1.6;
    }
    p.lead { font-size:17px; }
    p.last { margin-bottom:0px;}

    ul li {
      margin-left:5px;
      list-style-position: inside;
    }

    hr {
      border: 0;
      height: 0;
      border-top: 1px dotted rgba(0, 0, 0, 0.1);
      border-bottom: 1px dotted rgba(255, 255, 255, 0.3);
    }


    /* -------------------------------------
    Shopify
    ------------------------------------- */

    .products {
      width:100%;
      height:40px;padding
      margin:10px 0 10px 0;
    }
    .products img {
      float:left;
      height:40px;
      width:auto;
      margin-right:20px;
    }
    .products span {
      font-size:17px;
    }


    /* ---------------------------------------------------
    RESPONSIVENESS
    Nuke it from orbit. It's the only way to be sure.
    ------------------------------------------------------ */

    /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
    .container {
      display:block!important;
      max-width:600px!important;
      margin:0 auto!important; /* makes it centered */
      clear:both!important;
    }

    /* This should also be a block element, so that it will fill 100% of the .container */
    .content {
      padding: 15px 15px 0 15px;
      max-width:600px;
      margin:0 auto;
      display:block;
    }

    /* Let's make sure tables in the content area are 100% wide */
    .content table { width: 100%; }

    /* Be sure to place a .clear element after each set of columns, just to be safe */
    .clear { display: block; clear: both; }


    /* -------------------------------------------
    PHONE
    For clients that support media queries.
    Nothing fancy.
    -------------------------------------------- */
    @media only screen and (max-width: 600px) {

      a[class='btn'] { display:block!important; margin-bottom:10px!important; background-image:none!important; margin-right:0!important;}

      div[class='column'] { width: auto!important; float:none!important;}

      table.social div[class='column'] {
        width:auto!important;
      }

    }

    </style>
    </head>

    <body bgcolor='#FFFFFF'>\
    <table class='head-wrap' bgcolor='#FFFFFF'>
    <tr>
    <td></td>
    <td class='header container'>

    <div class='content'>
    <table bgcolor='#FFFFFF'>
    <tr>
    <td>

    </td>

    </tr>
    </table>
    </div>

    </td>
    <td></td>
    </tr>
    </table>
    <table class='body-wrap'>
    <tr>
    <td></td>
    <td class='container' bgcolor='#FFFFFF'>

    <div class='content'>
    <table>
    <tr>
    <td align='center'>
    </td>
    </tr>
    <tr>
    <td>
    <br/>
    <img src='cid:".$cid_logo."' height='173' width='581' alt='logo Telkomsigma' />
    <h2>Hi ".$userNameVP.",</h3>
    <br/>
    <h4> High priority project issue occured : </h4>
    <table>
    <tr>
    <td>
    Project Name
    </td>
    <td>
    ".$project_name."
    </td>
    </tr>
    <tr>
    <td>
    Project Manager
    </td>
    <td>
    ".$userNamePM."
    </td>
    </tr>
    <tr>
    <td>
    Subject
    </td>
    <td>
    ".$data['SUBJECT']."
    </td>
    </tr>
    <tr>
    <td>
    Note
    </td>
    <td>
    ".$data['NOTE']."
    </td>
    </tr>
    <tr>
    <td>
    Status
    </td>
    <td>
    ".$data['STATUS']."
    </td>
    </tr>
    </table>
    <br>

    <p style='text-align: left'>Trouble activating? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
    </td>
    </tr>

    </table>
    </div>

    </td>

    </tr>
    </table>
    <!-- /BODY -->

    <!-- FOOTER -->
    <table class='footer-wrap' bgcolor='#FFFFFF'>
    <tr>
    <td></td>
    <td class='container'>

    <!-- content -->
    <div class='content' style='margin-top: -15px'>
    <table>
    <tr>
    <br/>

    </br/>
    </tr>
    </table>
    </div>
    <!-- /content -->

    </td>
    <td></td>
    </tr>
    </table>

    </body>

    </html>");

        if($this->email->send()){
            echo "<script>alert('Sent')</script>".$this->email->print_debugger();
            redirect('/Detail_Project/view/'.$data['PROJECT_ID'].'#tab6');
        }

    }

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

    public function add_task()
    {
        //$id       = $this->M_detail_project->getMaxNumberWBSID();
        $project_id   = $this->input->post("PROJECT_ID");
        $IWO      = $this->M_detail_project->getIWO($project_id);


        //$data['ID']           = $id;
        $data['WBS_ID']         = $project_id;
        $data['WBS_PARENT_ID']      = $this->input->post("WBS_PARENT_ID");
        //$data['IWO_NO']         = $IWO;
        $data['PROJECT_ID']       = $this->input->post("PROJECT_ID");
        $data['WBS_NAME']         = $this->input->post("WBS_NAME");
        $data['WBS_DESC']         = $this->input->post("WBS_DESC");
        $data['PRIORITY']         = $this->input->post("PRIORITY");
        $data['CALCULATION_TYPE']     = $this->input->post("CALCULATION_TYPE");
        //$data['USER_TAG']         = $this->input->post("USER_TAG");
        //$data['PHASE']          = $this->input->post("PHASE");
        $data['EFFORT_DRIVEN']      = $this->input->post("EFFORT_DRIVEN");
        $data['START_DATE']       = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
        //$data['ACTUAL_START_DATE']    = "TO_DATE('".$this->input->post("ACTUAL_START_DATE")."','yyyy-mm-dd')";
        $data['FINISH_DATE']      ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";
        //$data['ACTUAL_FINISH_DATE']   = "TO_DATE('".$this->input->post("ACTUAL_FINISH_DATE")."','yyyy-mm-dd')";
        $data['DURATION']         = $this->input->post("DURATION");
        $data['WORK']           = $this->input->post("WORK");
        $data['MILESTONE']        = $this->input->post("MILESTONE");
        $data['WORK_COMPLETE']      = $this->input->post("WORK_COMPLETE");
        $data['WORK_PERCENT_COMPLETE']  = $this->input->post("WORK_PERCENT_COMPLETE");
        //$data['CONSTRAINT_TYPE']    = $this->input->post("CONSTRAINT_TYPE");
        //$data['CONSTRAINT_DATE']    = "TO_DATE('".$this->input->post("CONSTRAINT_DATE")."','yyyy-mm-dd')";
        //$data['DEADLINE']         = "TO_DATE('".$this->input->post("DEADLINE")."','yyyy-mm-dd')";
        //$data['ACHIEVEMENT']      = $this->input->post("ACHIEVEMENT");



        if (!isset($data)) {
            $this->session->set_flashdata("pesan", "<div class=\"alert alert-warning\" id=\"alert\"><i class=\"glyphicon glyphicon-ok\"></i> Data gagal disimpan</div>
                <script>
                setTimeout(function(){

             $('#alert').remove();
      location.reload();
    },1500);
    </script>");
            $project_id = $this->input->post("PROJECT_ID");
            $r = '/Detail_Project/view/'.$project_id;
            //  echo $r;
            redirect($r);
        } else {
            $project_id = $this->input->post("PROJECT_ID");
            $newid=$this->M_detail_project->insertWBS($data,$project_id);
            $WP_ID= $this->M_detail_project->getMaxWPID();
            $RP_ID= $this->M_detail_project->getMaxRPID();
            //$this->M_detail_project->insertWBSPool($data,$RP_ID,$WP_ID,$project_id);
            $this->session->set_flashdata("pesan", "<div class=\"alert alert-success\" id=\"alert\"><i class=\"glyphicon glyphicon-ok\"></i> Data berhasil disimpan</div><script>
    setTimeout(function(){

      $('#alert').remove();
      location.reload();
    },1500);
    </script>");
            $selWBS=$this->getSelectedWBS($newid);
            $allParent=$this->getAllParent($selWBS->WBS_ID);
            $dateStartWBS= new DateTime($selWBS->START_DATE);
            $dateEndWBS= new DateTime($selWBS->FINISH_DATE);
            foreach ($allParent as $ap) {
                $dateStartParent=new DateTime($ap->START_DATE);
                $dateEndParent=new DateTime($ap->FINISH_DATE);
                if ($dateStartWBS<$dateStartParent) {
                    $this->M_detail_project->updateParentDate('start',$ap->WBS_ID,$dateStartWBS->format('Y-m-d'));
                }
                if ($dateEndWBS>$dateStartParent) {
                    $this->M_detail_project->updateParentDate('end',$ap->WBS_ID,$dateEndWBS->format('Y-m-d'));
                }
                $this->M_detail_project->updateNewDuration($ap->WBS_ID);
            }
            $project_id = $this->input->post("PROJECT_ID");
            $r = '/Detail_Project/view/'.$project_id.'#tab2';
            //echo $r;
            redirect($r);

        }
        $project_id = $this->input->post("PROJECT_ID");
        $r = '/Detail_Project/view/'.$project_id;
        //  echo $r;

    }
    public function searchhome(){
        $bagian_unit = $this->datajson['userdata']['BU_ID'];
        $this->datajson['userdata']['PROFILE_NAME'] = $this->db->query("select PROF_NAME from profile  where PROF_ID = ".$this->datajson['userdata']['PROF_ID'])->row()->PROF_NAME;
        $query = $this->db->query("select BU_NAME FROM P_BU WHERE BU_ID='".$bagian_unit."'")->row();
        //$this->datajson['bussines_unit'] = $query->BU_NAME;
        //required data POST :
        // KEYWORD -> untuk iwo_no dan nama
        $this->project("search");
        $this->transformKeys($this->datajson);
        echo json_encode($this->datajson,JSON_NUMERIC_CHECK);
    }
    public function filterhome(){
        $bagian_unit = $this->datajson['userdata']['BU_ID'];
        $this->datajson['userdata']['PROFILE_NAME'] = $this->db->query("select PROF_NAME from profile  where PROF_ID = ".$this->datajson['userdata']['PROF_ID'])->row()->PROF_NAME;
        $query = $this->db->query("select BU_NAME FROM P_BU WHERE BU_ID='".$bagian_unit."'")->row();
        //$this->datajson['bussines_unit'] = $query->BU_NAME;
        //required data POST :
        // STATUS -> status project
        // PROJECT_TYPE -> project / non project
        // EFFORT_TYPE -> project/cr/manage operation/dll
        $this->project("filter");
        $this->transformKeys($this->datajson);
        echo json_encode($this->datajson,JSON_NUMERIC_CHECK);
    }

    public function inviteToBusiness(){
          $bu_id=$_POST['BU_ID'];
          $user_id=$_POST['USER_ID'];
          $c=array();
          //untuk mindah ke BU lain
          $c['data']=$this->M_business->ChangeMemberBU($user_id,$bu_id);
          if ($c['data']!=false) {
            //jika berhasil dipindah, kirim email
            $email =$this->M_business->getEmail($user_id);
            $name=$this->M_user->getName($email);
            $bu_name = $this->M_business->getBUName($bu_id);
            $this->sendVerificationManual($email,$name,$bu_name);
            $c['status']="Success";
            $c['message']="User berhasil diinvite";
          }else{
            $this->output->set_status_header(400);
            $c['status']="Error";
            $c['message']="User gagal diinvite";
          }

          echo json_encode($c);
        }
    function sendVerificationManual($email,$name,$bu_name){
        $this->load->library('email');
        $config['protocol']='smtp';
        $config['smtp_host']='smtp.sigma.co.id';
        $config['smtp_user']=SMTP_AUTH_USR;
        $config['smtp_pass']=SMTP_AUTH_PWD;
        $config['smtp_port']='587';
        $config['smtp_timeout']='100';
        $config['charset']    = 'utf-8';
        $config['newline']    = "\r\n";
        $config['mailtype'] = 'html';
        $config['validation'] = TRUE;
        $this->email->initialize($config);
        $this->email->from('prouds.support@sigma.co.id', 'Project & Resources Development System');
        $this->email->to($email);
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject('[PROUDS] Business Unit Invitation');
        $this->email->message("<!DOCTYPE html>
        <html>
        <head>
        <meta name='viewport' content='width=device-width' />
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <title>Account Activation</title>

        <style>
        /* -------------------------------------
        GLOBAL
        ------------------------------------- */
        * {
          margin:0;
          padding:0;
        }
        * { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; }

        img {
          max-width: 100%;
        }
        .collapse {
          margin:0;
          padding:0;
        }
        body {
          -webkit-font-smoothing:antialiased;
          -webkit-text-size-adjust:none;
          width: 100%!important;
          height: 100%;
        }


        /* -------------------------------------
        ELEMENTS
        ------------------------------------- */
        a { color: #2BA6CB;}

        .btn {
          text-decoration:none;
          color:#FFF;
          background-color: #1da1db;
          width:80%;
          padding:15px 10%;
          font-weight:bold;
          text-align:center;
          cursor:pointer;
          display:inline-block;
          border-radius: 5px;
          box-shadow: 3px 3px 3px 1px #EBEBEB;
        }

        p.callout {
          padding:15px;
          text-align:center;
          background-color:#ECF8FF;
          margin-bottom: 15px;
        }
        .callout a {
          font-weight:bold;
          color: #2BA6CB;
        }

        .column table { width:100%;}
        .column {
          width: 300px;
          float:left;
        }
        .column tr td { padding: 15px; }
        .column-wrap {
          padding:0!important;
          margin:0 auto;
          max-width:600px!important;
        }
        .columns .column {
          width: 280px;
          min-width: 279px;
          float:left;
        }
        table.columns, table.column, .columns .column tr, .columns .column td {
          padding:0;
          margin:0;
          border:0;
          border-collapse:collapse;
        }

        /* -------------------------------------
        HEADER
        ------------------------------------- */
        table.head-wrap { width: 100%;}

        .header.container table td.logo { padding: 15px; }
        .header.container table td.label { padding: 15px; padding-left:0px;}


        /* -------------------------------------
        BODY
        ------------------------------------- */
        table.body-wrap { width: 100%;}


        /* -------------------------------------
        FOOTER
        ------------------------------------- */
        table.footer-wrap { width: 100%;	clear:both!important;
        }
        .footer-wrap .container td.content  p { border-top: 1px solid rgb(215,215,215); padding-top:15px;}
        .footer-wrap .container td.content p {
          font-size:10px;
          font-weight: bold;

        }


        /* -------------------------------------
        TYPOGRAPHY
        ------------------------------------- */
        h1,h2,h3,h4,h5,h6 {
          font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; line-height: 1.1; margin-bottom:15px; color:#000;
        }
        h1 small, h2 small, h3 small, h4 small, h5 small, h6 small { font-size: 60%; color: #6f6f6f; line-height: 0; text-transform: none; }

        h1 { font-weight:200; font-size: 44px;}
        h2 { font-weight:200; font-size: 37px;}
        h3 { font-weight:500; font-size: 27px;}
        h4 { font-weight:500; font-size: 23px;}
        h5 { font-weight:900; font-size: 17px;}
        h6 { font-weight:900; font-size: 14px; text-transform: uppercase; color:#444;}

        .collapse { margin:0!important;}

        p, ul {
          margin-bottom: 10px;
          font-weight: normal;
          font-size:14px;
          line-height:1.6;
        }
        p.lead { font-size:17px; }
        p.last { margin-bottom:0px;}

        ul li {
          margin-left:5px;
          list-style-position: inside;
        }

        hr {
          border: 0;
          height: 0;
          border-top: 1px dotted rgba(0, 0, 0, 0.1);
          border-bottom: 1px dotted rgba(255, 255, 255, 0.3);
        }


        /* -------------------------------------
        Shopify
        ------------------------------------- */

        .products {
          width:100%;
          height:40px;padding
          margin:10px 0 10px 0;
        }
        .products img {
          float:left;
          height:40px;
          width:auto;
          margin-right:20px;
        }
        .products span {
          font-size:17px;
        }


        /* ---------------------------------------------------
        RESPONSIVENESS
        Nuke it from orbit. It's the only way to be sure.
        ------------------------------------------------------ */

        /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
        .container {
          display:block!important;
          max-width:600px!important;
          margin:0 auto!important; /* makes it centered */
          clear:both!important;
        }

        /* This should also be a block element, so that it will fill 100% of the .container */
        .content {
          padding: 15px 15px 0 15px;
          max-width:600px;
          margin:0 auto;
          display:block;
        }

        /* Let's make sure tables in the content area are 100% wide */
        .content table { width: 100%; }

        /* Be sure to place a .clear element after each set of columns, just to be safe */
        .clear { display: block; clear: both; }


        /* -------------------------------------------
        PHONE
        For clients that support media queries.
        Nothing fancy.
        -------------------------------------------- */
        @media only screen and (max-width: 600px) {

          a[class='btn'] { display:block!important; margin-bottom:10px!important; background-image:none!important; margin-right:0!important;}

          div[class='column'] { width: auto!important; float:none!important;}

          table.social div[class='column'] {
            width:auto!important;
          }

        }

        </style>
        </head>

        <body bgcolor='#FFFFFF'>\
        <table class='head-wrap' bgcolor='#FFFFFF'>
        <tr>
        <td></td>
        <td class='header container'>

        <div class='content'>
        <table bgcolor='#FFFFFF'>
        <tr>
        <td>

        </td>

        </tr>
        </table>
        </div>

        </td>
        <td></td>
        </tr>
        </table>
        <table class='body-wrap'>
        <tr>
        <td></td>
        <td class='container' bgcolor='#FFFFFF'>

        <div class='content'>
        <table>
        <tr>
        <td align='center'>
        </td>
        </tr>
        <tr>
        <td>
        <br/>
        <img src='cid:".$cid_logo."' height='173' width='581' alt='logo Telkomsigma' />
        <h2>Hi ".$name.",</h3>
        <br/>
        <h4>  You are Invited in Business Unit ".$bu_name." </h4>
        <br>

        <br/>
        <p style='text-align: left'>Trouble activating? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
        </td>
        </tr>

        </table>
        </div>

        </td>

        </tr>
        </table>
        <!-- /BODY -->

        <!-- FOOTER -->
        <table class='footer-wrap' bgcolor='#FFFFFF'>
        <tr>
        <td></td>
        <td class='container'>

        <!-- content -->
        <div class='content' style='margin-top: -15px'>
        <table>
        <tr>
        <br/>

        </br/>
        </tr>
        </table>
        </div>
        <!-- /content -->

        </td>
        <td></td>
        </tr>
        </table>

        </body>

        </html>");

        $this->email->send();
      }
}
