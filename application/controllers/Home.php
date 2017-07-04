<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {


    public $datajson = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_home');

        $this->datajson['userdata'] = $_SESSION;

    }

    public function index(){
        $this->datatimesheet();
        $this->assignment();
        print_r(json_encode($this->datajson));
    }

    /*FOR DATATIMESHEET THIS MONTH*/
    private function datatimesheet(){

            //parameter
            $tanggalnow = getdate();
            $_POST['bulan'] = $tanggalnow['mon'];
            $_POST['tahun'] = $tanggalnow['year'];
            $user_id=	$this->session->userdata('USER_ID');
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
    private function countDurationAll($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = $end->diff($start);
        $days = $interval->days;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
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
    private function assignment(){
        $user_id = $this->session->userdata('USER_ID');
        $this->datajson['task_user']=($this->M_home->assignmentView($user_id));
    }
}