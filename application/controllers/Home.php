<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {


    public $datajson = array();


    public function __construct()
    {
        session_start();
        parent::__construct();

        $this->load->model('M_home');
        $this->load->model('M_project');
        $this->load->model('M_business');
        $this->load->model('M_detail_project');
        $this->load->model('M_timesheet');
        $this->load->model('M_invite');
        $this->load->model('M_issue');
        $this->load->model('M_Member_Activity');
        //unset($_SESSION['userdata']['PASSWORD']);
        $this->datajson = $_SESSION;

    }

    public function index(){
        $bagian_unit = $_SESSION['userdata']['BU_ID'];

        $query = $this->db->query("select BU_NAME FROM P_BU WHERE BU_ID='".$bagian_unit."'")->row();
        $this->datajson['bussines_unit'] = $query->BU_NAME;
        $this->datatimesheet();
        $this->project();
        $this->transformKeys($this->datajson);
        print_r(json_encode($this->datajson));
    }

    /*FOR DATATIMESHEET THIS MONTH*/
    private function datatimesheet(){

            //parameter
            $tanggalnow = getdate();
            $_POST['bulan'] = $tanggalnow['mon'];
            $_POST['tahun'] = $tanggalnow['year'];
            $user_id=$_SESSION['userdata']['USER_ID'];
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
            if (($bulan==$m)&& ($tahun==$y) ){
                $hasil['entry']=$entry/$this->countDuration($tahun."/".$bulan."/1", date("Y/m/d")) *100;
            }
            else{
                $hasil['entry']=$entry/$this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun)) *100;
            }
            //Utilization calculation
            if (($bulan==$m)&& ($tahun==$y) ){
                $hasil['utilization']=$total_hours/($this->countDuration($tahun."/".$bulan."/1", date("Y/m/d"))*8) *100;
                $hasil['c']= ($this->countDuration($tahun."/".$bulan."/1", date("Y/m/d"))*8);
            }
            else{
                $hasil['utilization']=$total_hours/($this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun))*8) *100;
                $hasil['c']= ($this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun))*8);

            }
            //Utilization text
            if ($hasil['utilization']<70)
            {
                $hasil['status_utilization']='Under';
            }
            elseif (($hasil['utilization']>70)&& ($hasil['utilization']<=85)   ){
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
                $hasil['allentry'][$i][1]=$hasilAllentry['JML_ENTRY_BULANAN']/$durasi[$i]*100;

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
                $hasil['allhour'][$i][1]=($hasilAllhour['JML_JAM_BULANAN']/$durasihour[$i])*100;
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

    /*FOR ASSIGNMENT*/
    public function myassignment(){
        $user_id = $_SESSION['userdata']['USER_ID'];
        $data=array();

        $data['assignment']=($this->M_home->assignmentView($user_id));
        $this->transformKeys($data);
        print_r(json_encode($data));
    }

    /*For Activities*/
    public function myactivities(){
        $user_id = $_SESSION['userdata']['USER_ID'];
        $data=array();
        //$data['header']=($this->load->view('v_header'));
        //$data['float_button']=($this->load->view('v_floating_button'));
        //$data['nav']=($this->load->view('v_nav1'));
        //$data['assignment']=($this->M_home->assignmentView($user_id));
        //$data['pr_list']=$this->M_home->assignmentProject($user_id);
        $data['activity_Timesheet']=($this->M_timesheet->selectTimesheet($user_id));
        //$data['task_user']=($this->M_home->assignmentView($user_id));

        //$this->load->view('v_home_activity', $data);
        //$data['footer']=($this->load->view('v_footer2'));
        $this->transformKeys($data);
        print_r(json_encode($data));
    }

    public function projectactivities(){
        $project_id = $this->uri->segment(3);

        $data['project_activities'] =  $this->db->query("SELECT *
                                FROM USER_TIMESHEET
                                WHERE project_id = '".$project_id."'
                                ORDER BY ts_date DESC")->result_array();
        $this->transformKeys($data);
        print_r(json_encode($data));
    }

    /*FOR PROJECT LIST*/
    private function project(){
        $prof = $_SESSION['userdata']['PROF_ID'];
        $id = $_SESSION['userdata']['USER_ID'];
        $this->datajson['projects'] = $this->M_project->getUsersProject($id);
        //$id_bu = $this->session->userdata('BU_ID');
        //$this->datajson['tampil_Timesheet']=($this->M_timesheet->selectTimesheet2($id_bu));
    }

    /*FOR DETAIL PROJECT*/
    public function detailproject(){
        //print_r($_SESSION);
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
        $data['project_detail']=$this->M_detail_project->getProjectDetail($id);
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
        $this->datajson['project_detail'] = $data;
        $this->datajson['overview']['IWO'] = $this->datajson["project_detail"]["project_detail"]["IWO_NO"];
        $this->datajson['overview']['BU_OWNER']=$this->datajson["project_detail"]["project_detail"]["BU_NAME"];
        $this->datajson['overview']['DESCRIPTION']=$this->datajson["project_detail"]["project_detail"]["PROJECT_DESC"];

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
        $this->datajson['project_team'] = $this->db->query("select * from V_PROJECT_TEAM_MEMBER where project_id = ".$this->uri->segment(3))->result_array();
        foreach ($this->datajson['project_team'] as $key=>$value){
            $this->transformKeys($this->datajson['project_team'][$key]);
        }
        unset($this->datajson["project_detail"]);

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

        $this->datajson['project_doc_list'] = $this->db->query("select * from project_doc where project_id = $projectid")->result_array();
        foreach ($this->datajson['project_doc_list'] as $key=>$value){
            $this->transformKeys($this->datajson['project_doc_list'][$key]);
        }

        $this->transformKeys($this->datajson);
        print_r(json_encode($this->datajson));
    }

    /*Issue Manajement*/
    public function projectissue(){

        $projectid = $this->uri->segment(3);

        $this->datajson['project_issue_list'] = $this->M_home->projectissuelist($projectid);

        $this->transformKeys($this->datajson);
        print_r(json_encode($this->datajson));
    }

    public function addissue(){

        $returnmessage = array();

        $config['upload_path']		= 'assets/p_issue/';
        $config['allowed_types']	= 'zip|doc|docs|docx|xls|pdf|xlsx';
        $config['max_size']			= 5020;
        $config['max_width']		= 1024;
        $config['max_height']		= 768;
        //$config['file_name']		= $nm;
        $this->load->library('upload', $config);
        if (! $this->upload->do_upload('file_upload')){
            $data['upload_data']= $this->upload->data();
            $id = $this->M_issue->getMaxIssue();
            $data['ISSUE_ID'] 			= $id;
            $data['USER_ID'] 			= $_SESSION['userdata']['USER_ID'];
            $data['PRIORITY'] 		    = $this->input->post("PRIORITY");
            $data['STATUS'] 			= "On Progress";
            $data['SUBJECT'] 			= $this->input->post("SUBJECT");
            $data['NOTE']			 	= $this->input->post("MESSAGE");
            $data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");
            //$data['EVIDENCE'] 			= $this->upload->data('file_name');

            if($data['PRIORITY']=='High'){
                $id = $this->M_issue->getMaxIssue();
                $data['ISSUE_ID'] 			= $id;
                $data['USER_ID'] 			= $_SESSION['userdata']['USER_ID'];
                $USER_ID		 			= $_SESSION['userdata']['USER_ID'];
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


            $this->M_issue->insertIssue($data);
            $id_det= $this->M_issue->getMaxDetIssue();
            $this->M_issue->insertDetIssue3($data,$id_det);
            //redirect('/Detail_Project/view/'.$data['PROJECT_ID'].'#tab6');
            $returnmessage['title'] = "Success";
            $returnmessage['message'] = "berhasil tambah issue";



        }else{
            $data['upload_data']= $this->upload->data();
            $id = $this->M_issue->getMaxIssue();
            $data['ISSUE_ID'] 			= $id;
            $data['USER_ID'] 			= $_SESSION['userdata']['USER_ID'];
            $data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");
            $data['PRIORITY'] 			= $this->input->post("PRIORITY");
            $data['STATUS'] 			= "On Progress";
            $data['SUBJECT'] 			= $this->input->post("SUBJECT");
            $data['NOTE']			 	= $this->input->post("MESSAGE");
            $data['EVIDENCE'] 			= $this->upload->data('file_name');

            if($data['PRIORITY']=='High'){
                $USER_ID		 			= $_SESSION['userdata']['USER_ID'];
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
            $this->M_issue->insertIssue2($data);
            $id_det= $this->M_issue->getMaxDetIssue();
            $this->M_issue->insertDetIssue2($data,$id_det);
            //redirect('/Detail_Project/view/'.$data['PROJECT_ID'].'#tab6');
            $returnmessage['title'] = "both";
            $returnmessage['message'] = "berhasil tambah issue,tetapi gagal upload foto";
        }

        print_r(json_encode($returnmessage));
    }

    /*Upload Document*/
    public function documentupload(){

        $projectid = $this->uri->segment(3);

        $config['upload_path']          = 'assets/p_docs';
        $config['allowed_types']        = 'zip|doc|docs|docx|xls|pdf|xlsx|jpg|jpeg|png';
        $config['max_size']             = 5020;
        $config['remove_spaces']        = true;
        $config['overwrite']            = false;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('document'))
        {
            $error['title']=['error'];
            $error['message'] = ['gagal upload dokumen'];
            print_r(json_encode($error));
        }
        else{
            $data = array('upload_data' => $this->upload->data());
            $newid = $this->db->query("select max(DOC_ID) as id from project_doc")->row();
            $insert = array(
                'DOC_ID' => intval($newid->ID),
                'PROJECT_ID' => $projectid,
                'DOC_NAME' => $data['upload_data']['file_name'],
                'URL' => $data['upload_data']['file_name'],
                'DATE_UPLOAD' => date("d-M-Y"),
                'UPLOAD_BY' => $_SESSION['userdata']['USER_ID'],
                'DOC_DESC' => $this->input->post('desc')
            );

            $this->db->insert('PROJECT_DOC', $insert);

            $message['title']=['success'];
            $message['message'] = ['Berhasil upload dokumen'];
            print_r(json_encode($message));
        }
    }

    function sendVerification($USER_ID,$userNamePM,$bu,$USER_VP,$userNameVP,$email_vp,$data,$project_name){
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
        //  $this->email->to($email_vp);
        $this->email->cc('pmo@sigma.co.id');
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

    function transformKeys(&$array)
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



}


