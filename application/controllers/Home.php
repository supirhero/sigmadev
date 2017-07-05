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

        $this->datajson = $_SESSION;

    }

    public function index(){
        $bagian_unit = $_SESSION['userdata']['BU_ID'];

        $query = $this->db->query("select BU_NAME FROM P_BU WHERE BU_ID='".$bagian_unit."'")->row();
        $this->datajson['bussines_unit'] = $query->BU_NAME;
        $this->datatimesheet();
        $this->project();
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
    /*private function assignment(){
        $user_id = $this->session->userdata('USER_ID');



        $this->datajson['task_user']=($this->M_home->assignmentView($user_id));
    }*/
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
        $this->datajson['project_workplan_status']['task'] = $this->db->query("select * from cari_task where project_id = ".$this->uri->segment(3))->result();

        //Project Performance Index
        $this->datajson['project_performance_index']['pv'] = $this->datajson['project_detail']['pv'];
        $this->datajson['project_performance_index']['ev'] = $this->datajson['project_detail']['ev'];
        $this->datajson['project_performance_index']['ac'] = $this->datajson['project_detail']['ac'];
        $this->datajson['project_performance_index']['cpi'] = $this->datajson['project_detail']['cpi'];
        $this->datajson['project_performance_index']['spi'] = $this->datajson['project_detail']['spi'];

        //Project Team
        $this->datajson['project_team'] = $this->datajson['project_detail']["dataProject"];
        unset($this->datajson["project_detail"]);

        print_r(json_encode($this->datajson));
///

    }


}


