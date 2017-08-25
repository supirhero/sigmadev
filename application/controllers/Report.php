<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report extends CI_Controller {

    private $datajson =array();
    public function __construct()
    {
        parent::__construct();
        error_reporting(E_ALL & ~E_NOTICE);
        $this->load->model('M_home');
        $this->load->model('M_timesheet');
        $this->load->model('M_report');
        $this->load->model('M_business');
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



        /*//TOKEN LOGIN CHECKER
        if(isset($_SERVER['HTTP_TOKEN'])){
            $decoded_user_data =(array) $this->token->decodetoken($_SERVER['HTTP_TOKEN']);
            $this->datajson['token'] = $_SERVER['HTTP_TOKEN'];
        }
        elseif(isset($_GET['token'])){
            $decoded_user_data =(array) $this->token->decodetoken($_GET['token']);
            $this->datajson['token'] = $_GET['token'];
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
        }*/

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
        }
        else{
            $hasil['utilization']=$total_hours/($this->countDuration($tahun."/".$bulan."/1", $this->last_day($bulan,$tahun))*8) *100;

        }
        //Utilization text
        if ($hasil['utilization']<80)
        {
            $hasil['status_utilization']='Under';
        }
        elseif (($hasil['utilization']>=80)&& ($hasil['utilization']<=100)   ){
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



        /************************************************/
        /*entry*/
        $allentry=$this->M_home->getAllEntry($user_id,$tahun);
        $hasil['allentry'] = [];
        foreach ($allentry as $hasilAllentry) {
            $dateObj   = DateTime::createFromFormat('!m', $hasilAllentry['MONTH_VALUE']);
            // March
            if (($dateObj->format('m')==$m)&& ($tahun==$y) ){

                $durasi=($this->countDuration($tahun."/".$dateObj->format('m')."/1", date("Y/m/d")));
            }
            else{
                $durasi=($this->countDuration($tahun."/".$dateObj->format('m')."/1", $this->last_day($dateObj->format('m'),$tahun)));
            }
            array_push($hasil['allentry'],['label'=>$hasilAllentry['MONTH_DISPLAY'],'value'=>$hasilAllentry['JML_ENTRY_BULANAN']/$durasi*100]);

        }


        /************************************************/
        /*utilization*/
        $hasil['allhour']=[];
        $allhour=$this->M_home->getAllHour($user_id,$tahun);
        foreach ($allhour as $hasilAllhour) {

            $dateObj   = DateTime::createFromFormat('!m', $hasilAllhour['MONTH_VALUE']);
            // March
            if (($dateObj->format('m')==$m)&& ($tahun==$y) ){

                $durasihour=($this->countDuration($tahun."/".$dateObj->format('m')."/1", date("Y/m/d"))*8);
            }
            else{
                $durasihour=($this->countDuration($tahun."/".$dateObj->format('m')."/1", $this->last_day($dateObj->format('m'),$tahun))*8);
            }
            //$hasil['anjay'][$i] = $this->last_day($dateObj->format('m'),$tahun);
            array_push($hasil['allhour'],['label'=>$hasilAllhour['MONTH_DISPLAY'],'value'=>$hasilAllhour['JML_JAM_BULANAN']/$durasihour*100]);
        }

        $hasil['total_hours']=$total_hours;
        $this->transformKeys($hasil);
        echo json_encode($hasil, JSON_NUMERIC_CHECK);
    }

    public function myperformances_yearly(){
        $y=(int)date("Y");
        $m=(int)date("m");

        $tahun = $this->input->post('tahun');
        $user_id=	$this->datajson['userdata']['USER_ID'];

        /************************************************/
        /*entry*/
        $allentry=$this->M_home->getAllEntry($user_id,$tahun);
        $hasil['allentry'] = [];
        foreach ($allentry as $hasilAllentry) {
            $dateObj   = DateTime::createFromFormat('!m', $hasilAllentry['MONTH_VALUE']);
            // March
            if (($dateObj->format('m')==$m)&& ($tahun==$y) ){

                $durasi=($this->countDuration($tahun."/".$dateObj->format('m')."/1", date("Y/m/d")));
            }
            else{
                $durasi=($this->countDuration($tahun."/".$dateObj->format('m')."/1", $this->last_day($dateObj->format('m'),$tahun)));
            }
            array_push($hasil['allentry'],['label'=>$hasilAllentry['MONTH_DISPLAY'],'value'=>$hasilAllentry['JML_ENTRY_BULANAN']/$durasi*100]);

        }


        /************************************************/
        /*utilization*/
        $hasil['allhour']=[];
        $allhour=$this->M_home->getAllHour($user_id,$tahun);
        foreach ($allhour as $hasilAllhour) {

            $dateObj   = DateTime::createFromFormat('!m', $hasilAllhour['MONTH_VALUE']);
            // March
            if (($dateObj->format('m')==$m)&& ($tahun==$y) ){

                $durasihour=($this->countDuration($tahun."/".$dateObj->format('m')."/1", date("Y/m/d"))*8);
            }
            else{
                $durasihour=($this->countDuration($tahun."/".$dateObj->format('m')."/1", $this->last_day($dateObj->format('m'),$tahun))*8);
            }
            //$hasil['anjay'][$i] = $this->last_day($dateObj->format('m'),$tahun);
            array_push($hasil['allhour'],['label'=>$hasilAllhour['MONTH_DISPLAY'],'value'=>$hasilAllhour['JML_JAM_BULANAN']/$durasihour*100]);
        }
        echo json_encode($hasil);
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
        $fixdata = ['directorat'=>[],'company'=>[],'business_unit'=>[]];
        foreach($data_bu as $data){

            if ($data['BU_ID'] == 0){
                array_push($fixdata['company'],$data);
            }
            elseif($data['BU_PARENT_ID'] == 0){
                array_push($fixdata['directorat'],$data);
            }
            else{
                array_push($fixdata['business_unit'],$data);
            }
        }
        echo json_encode($fixdata);
    }

    //https://marvelapp.com/hj9eb56/screen/29382899
    public function r_directoratbu(){
        $bu = $_POST['bu'];
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
            if ($persen_utilization<80)
            {
                $text_utilization='Under';
            }
            elseif (($persen_utilization>=80)&& ($persen_utilization<=100)   ){
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
    //resource per bu
    public function r_entry_bu(){

        $tahun = $this->input->post('tahun');
        // $tahun = '2016';
        //  $bu = 37;
        $bu = $this->input->post('bu_id');

        $y=(int)date("Y");
        // $m=(int)date("m");
        //echo print_r ($thn);


        $count_user=$this->M_report->getCountUser($bu);
        //echo print_r($tahun);

        if (($tahun==$y)){
            $res['jml_entry']=round($this->M_report->getEntryBUYearly($bu,$tahun)/$this->countDuration($tahun."/1/1", date("Y/m/d")) *100/$count_user,2);
        }
        else{
            $res['jml_entry']=round($this->M_report->getEntryBUYearly($bu,$tahun)/$this->countDuration($tahun."/1/1", $tahun."/12/31") *100/$count_user,2);

        }
        // Entry text
        if ($res['jml_entry']<100)
        {
            $res['status']='Under';
        }
        elseif ($res['jml_entry']==100) {
            $res['status']='Complete';
        }
        else {
            $res['status']='Over';
        }


        $allentry=$this->M_report->gettahunanbu($bu,$tahun);
        $res['allentry']=[];
        $i=1;
        foreach ($allentry as $has) {

            $res['allentry'][$i][0]= $has['BULAN'];
            $res['allentry'][$i][1]=$has['JML_ENTRY_BULANAN']*100/($count_user*$this->getdurationmonth($has['BULAN'],$tahun));
            $i++;
        }

        //   json_encode($res,JSON_NUMERIC_CHECK);
        //$i=1;
        /*  $data['res'][0]=array('Month','Entry');

        foreach ($res as $r) {



        //  $hasil['res'][$i][0]=  $dateObj->format('M');
        $data['res'][$i][0]=  $r['JML_ENTRY_BULANAN'];
        $data['res'][$i][1]=  $r['BULAN'];

        $i++;


      }*/
        echo json_encode($res,JSON_NUMERIC_CHECK);




    }

    //resource per bu
    public function r_util_bu(){

        $tahun = $this->input->post('tahun');
        // $tahun = '2016';
        //  $bu = 37;
        $bu = $this->input->post('bu_id');

        $y=(int)date("Y");
        // $m=(int)date("m");
        //echo print_r ($thn);


        $count_user=$this->M_report->getCountUser($bu);
        //echo print_r($tahun);

        if (($tahun==$y)){

            $res['jml_util']=round($this->M_report->getUtilBUYearly($bu,$tahun)/($this->countDuration($tahun."/1/1", date("Y/m/d"))*8) *100/$count_user,2);
        }
        else{

            $res['jml_util']=round($this->M_report->getUtilBUYearly($bu,$tahun)/($this->countDuration($tahun."/1/1", $tahun."/12/31")*8) *100/$count_user,2);

        }


        //Utilization text
        if ($res['jml_util']<80)
        {
            $res['status_utilization']='Under';
        }
        elseif (($res['jml_util']>=80)&& ($res['jml_util']<=100)   ){
            $res['status_utilization']='Optimal';
        }
        else {
            $res['status_utilization']='Over';
        }

        $allhour=$this->M_report->getAllHourBU($bu,$tahun);
        $res['allhour']=[];
        $i=1;
        foreach ($allhour as $hus) {
            $res['allhour'][$i][0]= $hus['BULAN'];
            $res['allhour'][$i][1]=$hus['JML_ENTRY_BULANAN']*100/(8*$count_user*$this->getdurationmonth($hus['BULAN'],$tahun));
            $i++;
        }

        //   json_encode($res,JSON_NUMERIC_CHECK);
        //$i=1;
        /*  $data['res'][0]=array('Month','Entry');

        foreach ($res as $r) {



        //  $hasil['res'][$i][0]=  $dateObj->format('M');
        $data['res'][$i][0]=  $r['JML_ENTRY_BULANAN'];
        $data['res'][$i][1]=  $r['BULAN'];

        $i++;


      }*/
        echo json_encode($res,JSON_NUMERIC_CHECK);
    }

    function getdurationmonth($month,$tahun){
        switch ($month) {
            case '01':
                $dur=$this->countDuration($tahun."/1/1", $tahun."/1/31");
                break;
            case '02':
                $dur=$this->countDuration($tahun."/2/1", $tahun."/2/28");
                break;
            case '03':
                $dur=$this->countDuration($tahun."/3/1", $tahun."/3/31");
                break;
            case '04':
                $dur=$this->countDuration($tahun."/4/1", $tahun."/4/30");
                break;
            case '05':
                $dur=$this->countDuration($tahun."/5/1", $tahun."/5/31");
                break;
            case '06':
                $dur=$this->countDuration($tahun."/6/1", $tahun."/6/30");
                break;
            case '07':
                $dur=$this->countDuration($tahun."/7/1", $tahun."/7/31");
                break;
            case '08':
                $dur=$this->countDuration($tahun."/8/1", $tahun."/8/31");
                break;
            case '09':
                $dur=$this->countDuration($tahun."/9/1", $tahun."/9/30");
                break;
            case '10':
                $dur=$this->countDuration($tahun."/10/1", $tahun."/10/31");
                break;
            case '11':
                $dur=$this->countDuration($tahun."/11/1", $tahun."/11/30");
                break;
            case '12':
                $dur=$this->countDuration($tahun."/12/1", $tahun."/12/31");
                break;
        }
        return $dur;
    }
    //report overview
    public function r_overview(){

        $date=date("M-Y");
        $query = $this->db->query("select b.bu_name,b.bu_code, b.bu_alias,b.bu_id,count(c.project_id) as jml_project_cr,
                round(sum(ev)/count(c.project_id),2) as EV,
                round(sum(pv)/count(c.project_id),2) as PV,
                round(sum(AC)/count(c.project_id),2) as AC,
                case when round(sum(ev)/sum(pv),2)<1 and round(sum(ev)/sum(pv),2) not in (0) then '0'||round(sum(ev)/sum(pv),2) else to_char(round(sum(ev)/sum(pv),2)) end as SPI,
                case when sum(ac)=0 then '0' when round(sum(ev)/sum(ac),2)<1 and round(sum(ev)/sum(ac),2)>0 then '0'||round(sum(ev)/sum(ac),2) else to_char(round(sum(ev)/sum(ac),2)) end as CPI
             from (select (max(ev)-min(ev)) as ev,(max(pv)-min(pv)) as pv,case when (max(ev)-min(ev))=0 then 0 else (max(ac)-min(ac)) end as ac,
case when (max(pv)-min(pv))=0 then 0 else round((max(ev)-min(ev))/(max(pv)-min(pv)),2) end as spi,
case when (max(ac)-min(ac))=0 then 1 when round((max(ev)-min(ev))/(max(ac)-min(ac)),2)>1 then 1 else round((max(ev)-min(ev))/(max(ac)-min(ac)),2) end as cpi,
project_id
from tb_rekap_project
where  to_char(tanggal,'Mon-YYYY')='$date'
group by project_id) a inner join
            projects c on c.project_id=a.project_id
            inner join p_bu b on (b.bu_code=c.bu_code OR b.bu_alias=c.bu_code)
            where project_status='In Progress' and c.PROJECT_TYPE_ID='Project' 
and type_of_effort in (1,2)
and pv!='0' 
and b.BU_CODE !='PROUDS'
and b.BU_code !='GTS'
and b.BU_code !='NSM' 
group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id
            order by b.bu_name");
$result["r_monthly"] = $query->result();
            $year=date("Y");

        $listBU=explode(",",$this->input->post('bu_aliases'));
        foreach ($listBU as &$value) {
            $list[] = "'".$value."'";
        }
        $listBU = implode(",",$list);
        for($i=1; $i<=12; $i++)
        {
            $month = date("M", mktime(0, 0, 0, $i, 10));
            $query = $this->db->query("select b.bu_name,b.bu_code, b.bu_alias,b.bu_id,count(c.project_id) as jml_project_cr,
                round(sum(ev)/count(c.project_id),2) as EV,
                round(sum(pv)/count(c.project_id),2) as PV,
                round(sum(AC)/count(c.project_id),2) as AC,
                case when round(sum(ev)/sum(pv),2)<1 and round(sum(ev)/sum(pv),2) not in (0) then '0'||round(sum(ev)/sum(pv),2) else to_char(round(sum(ev)/sum(pv),2)) end as SPI,
                case when sum(ac)=0 then '0' when round(sum(ev)/sum(ac),2)<1 and round(sum(ev)/sum(ac),2)>0 then '0'||round(sum(ev)/sum(ac),2) else to_char(round(sum(ev)/sum(ac),2)) end as CPI
             from (select (max(ev)-min(ev)) as ev,(max(pv)-min(pv)) as pv,case when (max(ev)-min(ev))=0 then 0 else (max(ac)-min(ac)) end as ac,
case when (max(pv)-min(pv))=0 then 0 else round((max(ev)-min(ev))/(max(pv)-min(pv)),2) end as spi,
case when (max(ac)-min(ac))=0 then 1 when round((max(ev)-min(ev))/(max(ac)-min(ac)),2)>1 then 1 else round((max(ev)-min(ev))/(max(ac)-min(ac)),2) end as cpi,
project_id
from tb_rekap_project
where  to_char(tanggal,'Mon-YYYY')='$month-$year'
group by project_id) a inner join
            projects c on c.project_id=a.project_id
            inner join p_bu b on (b.bu_code=c.bu_code OR b.bu_alias=c.bu_code)
            where project_status='In Progress' and c.PROJECT_TYPE_ID='Project' 
and type_of_effort in (1,2)
and pv!='0' 
and b.BU_CODE !='PROUDS'
and b.BU_code !='GTS'
and b.BU_code !='NSM' 
and b.BU_alias in ($listBU)
group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id
            order by b.bu_name");
            $result["r_yearly"][$i] = $query->result();
            $result["r_yearly"][$i]["month_name"] = $month;
        }
        echo json_encode($result);
    }

    //report monthly overview
    public function r_monthly(){
        $tahun = $this->input->post('tahun');
        $month = date("M", mktime(0, 0, 0, $this->input->post('bulan'), 10));

        $query = $this->db->query("select b.bu_name,b.bu_code, b.bu_alias,b.bu_id,count(c.project_id) as jml_project_cr,
                round(sum(ev)/count(c.project_id),2) as EV,
                round(sum(pv)/count(c.project_id),2) as PV,
                round(sum(AC)/count(c.project_id),2) as AC,
                case when round(sum(ev)/sum(pv),2)<1 and round(sum(ev)/sum(pv),2) not in (0) then '0'||round(sum(ev)/sum(pv),2) else to_char(round(sum(ev)/sum(pv),2)) end as SPI,
                case when sum(ac)=0 then '0' when round(sum(ev)/sum(ac),2)<1 and round(sum(ev)/sum(ac),2)>0 then '0'||round(sum(ev)/sum(ac),2) else to_char(round(sum(ev)/sum(ac),2)) end as CPI
             from (select (max(ev)-min(ev)) as ev,(max(pv)-min(pv)) as pv,case when (max(ev)-min(ev))=0 then 0 else (max(ac)-min(ac)) end as ac,
case when (max(pv)-min(pv))=0 then 0 else round((max(ev)-min(ev))/(max(pv)-min(pv)),2) end as spi,
case when (max(ac)-min(ac))=0 then 1 when round((max(ev)-min(ev))/(max(ac)-min(ac)),2)>1 then 1 else round((max(ev)-min(ev))/(max(ac)-min(ac)),2) end as cpi,
project_id
from tb_rekap_project
where  to_char(tanggal,'Mon-YYYY')='$month-$tahun'
group by project_id) a inner join
            projects c on c.project_id=a.project_id
            inner join p_bu b on (b.bu_code=c.bu_code OR b.bu_alias=c.bu_code)
            where project_status='In Progress' and c.PROJECT_TYPE_ID='Project' 
and type_of_effort in (1,2)
and pv!='0' 
and b.BU_CODE !='PROUDS'
and b.BU_code !='GTS'
and b.BU_code !='NSM' 
group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id
            order by b.bu_name");
        $result["r_monthly"] = $query->result();
        echo json_encode($result);
    }
    //report yearly overview
    public function r_yearly($year=false){
if(!$year)
{
    $year=date("Y");
}
$listBU=explode(",",$this->input->post('bu_aliases'));
        foreach ($listBU as &$value) {
            $list[] = "'".$value."'";
        }
$listBU = implode(",",$list);
        for($i=1; $i<=12; $i++)
{
    $month = date("M", mktime(0, 0, 0, $i, 10));
    $query = $this->db->query("select b.bu_name,b.bu_code, b.bu_alias,b.bu_id,count(c.project_id) as jml_project_cr,
                round(sum(ev)/count(c.project_id),2) as EV,
                round(sum(pv)/count(c.project_id),2) as PV,
                round(sum(AC)/count(c.project_id),2) as AC,
                case when round(sum(ev)/sum(pv),2)<1 and round(sum(ev)/sum(pv),2) not in (0) then '0'||round(sum(ev)/sum(pv),2) else to_char(round(sum(ev)/sum(pv),2)) end as SPI,
                case when sum(ac)=0 then '0' when round(sum(ev)/sum(ac),2)<1 and round(sum(ev)/sum(ac),2)>0 then '0'||round(sum(ev)/sum(ac),2) else to_char(round(sum(ev)/sum(ac),2)) end as CPI
             from (select (max(ev)-min(ev)) as ev,(max(pv)-min(pv)) as pv,case when (max(ev)-min(ev))=0 then 0 else (max(ac)-min(ac)) end as ac,
case when (max(pv)-min(pv))=0 then 0 else round((max(ev)-min(ev))/(max(pv)-min(pv)),2) end as spi,
case when (max(ac)-min(ac))=0 then 1 when round((max(ev)-min(ev))/(max(ac)-min(ac)),2)>1 then 1 else round((max(ev)-min(ev))/(max(ac)-min(ac)),2) end as cpi,
project_id
from tb_rekap_project
where  to_char(tanggal,'Mon-YYYY')='$month-$year'
group by project_id) a inner join
            projects c on c.project_id=a.project_id
            inner join p_bu b on (b.bu_code=c.bu_code OR b.bu_alias=c.bu_code)
            where project_status='In Progress' and c.PROJECT_TYPE_ID='Project' 
and type_of_effort in (1,2)
and pv!='0' 
and b.BU_CODE !='PROUDS'
and b.BU_code !='GTS'
and b.BU_code !='NSM' 
group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id
            order by b.bu_name");
    $result["r_yearly"][$i] = $query->result();
    $result["r_yearly"][$i]["month_name"] = $month;
}
        echo json_encode($result);
    }


}