<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reporttest extends CI_Controller {
    private $datajson =array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_home');
        $this->load->model('M_timesheet');
        $this->load->model('M_report');
        $this->load->model('M_business');

        $this->datajson['userdata'] = json_decode("{
    \"USER_ID\": \"S201502162\",
    \"USER_NAME\": \"GINA KHAYATUNNUFUS\",
    \"EMAIL\": \"gina.nufus@sigma.co.id\",
    \"BU_ID\": \"36\",
    \"USER_TYPE_ID\": \"int\",
    \"SUP_ID\": \"S201404159\",
    \"PROF_ID\": \"6\",
    \"last_login\": \"17-JUL-17\",
    \"logged_in\": true
  }", true);


    }

    public function myperformances(){
        //parameter
        $user_id=	$this->datajson['userdata']['USER_ID'];
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
        $hasil['bulan']=$bulan;
        $hasil['tahun']=$tahun;
        $hasil['a']=$entry;
        $hasil['total_hours']=$total_hours;
        $hasil['b']= $this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun));

        /*remove unused variable*/
        unset($hasil['a']);
        unset($hasil['b']);
        unset($hasil['c']);
        unset($hasil['bulan']);
        unset($hasil['tahun']);
        unset($hasil['total_hours']);

        $this->transformKeys($hasil);
        echo json_encode($hasil, JSON_NUMERIC_CHECK);
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

    public function myactivities(){
        $user_id = $this->datajson['userdata']['USER_ID'];
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

    //get list all bu
    public function r_list_bu(){
        $data_bu = $this->M_business->getAllBU();
        echo json_encode($data_bu);
    }

    //https://marvelapp.com/hj9eb56/screen/29382899
    public function r_directoratbu(){
        $bu = $_POST['bu'   ];
        $tahun = $_POST['tahun'];
        $data =array();
        $data['project']['completed'] = $this->M_report->Portofolio_completed_Project($bu,$tahun);
        $data['project']['in_progress']= $this->M_report->Portofolio_Active_Project($bu,$tahun);
        $data['project']['not_started'] = $this->M_report->Portofolio_notstarted_Project($bu,$tahun);
        $data['project']['jumlah']= $this->M_report->Portofolio_Total_Project($bu,$tahun);
        $data['finance']['total_project_value'] = $this->M_report->Portofolio_Total_Project_Value($bu,$tahun);
        print_r(json_encode($data));
    }

    //https://marvelapp.com/hj9eb56/screen/29382902
    public function r_people(){
        $bu_id = $_POST['BU_ID'];
        $bulan = $_POST['BULAN'];
        $tahun = $_POST['TAHUN'];

        $y=(int)date("Y");
        $m=(int)date("m");

        $datareport=$this->M_report->get_utilization_on_bu($bu_id);

        for($i = 0 ; $i <count($datareport);$i++){

            //utilization
            $utilization=$this->M_report->getTotalHour($datareport[$i]['USER_ID'],$bulan,$tahun);
            $entry=$this->M_report->getEntry($datareport[$i]['USER_ID'],$bulan,$tahun);

            //entry
            if (($bulan==$m)&& ($tahun==$y) ){
                $persen_entry=$entry/$this->countDuration($tahun."/".$bulan."/1", date("Y/m/d")) *100;
            }
            else{
                $persen_entry=$entry/$this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun)) *100;
            }

            if ($persen_entry<100)
            {
                $text_entry='Under';
            }
            elseif ($persen_entry==100) {
                $text_entry='Complete';
            }
            else {
                $text_entry='Over';
            }

            //utilization
            if (($bulan==$m)&& ($tahun==$y) ){
                $persen_utilization=$utilization/($this->countDuration($tahun."/".$bulan."/1", date("Y/m/d"))*8) *100;
            }
            else{
                $persen_utilization=$utilization/($this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun))*8) *100;

            }
            if ($persen_utilization<70)
            {
                $text_utilization='Under';
            }
            elseif (($persen_utilization>70)&& ($persen_utilization<=85)   ){
                $text_utilization='Optimal';
            }
            else {
                $text_utilization='Over';
            }


            $datareport[$i]['utilisasi']=round($persen_utilization,2);
            $datareport[$i]['status_utilisasi']=$text_utilization;
            $datareport[$i]['entry']=round($persen_entry,2);
            $datareport[$i]['status_entry']=$text_entry;

        }
        echo json_encode($datareport);
    }

    //report overview
    public function r_overview(){
        $data['report_onprogress_project'] = $this->M_report->dashboard_all();

        echo json_encode($data);
    }
}