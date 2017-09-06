<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report extends CI_Controller {

    private $datajson =array();
    public $bu_id = [];
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
        error_reporting(E_ALL & ~E_NOTICE);
        $this->load->model('M_home');
     //   $this->load->model('M_timesheet');
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
        //newest
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
            order by al.type asc
            ")->result_array();
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
                            $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['BU']."'")->row()->BU_ID;
                            break;
                            case '3':
                            $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
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
                }
                else{
                    $will_die = 1;
                }
                if($will_die ==1){
                    $this->output->set_status_header(403);
                    $returndata['status'] = 'failed';
                    $returndata['message'] = 'Anda tidak bisa mengakses feature yang ada di business unit ini';
                    echo json_encode($returndata);
                    die;
                }
            }
        }
        /*===============================================================================*/


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
            if($hasilAllentry['JML_ENTRY_BULANAN']>0 && $durasi >0)
                array_push($hasil['allentry'],['label'=>substr($hasilAllentry['MONTH_DISPLAY'],0,3),'value'=>$hasilAllentry['JML_ENTRY_BULANAN']/$durasi*100]);
            else
                array_push($hasil['allentry'],['label'=>substr($hasilAllentry['MONTH_DISPLAY'],0,3),'value'=>0]);

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
            if($hasilAllhour['JML_JAM_BULANAN']>0 && $durasihour >0)
                array_push($hasil['allhour'],['label'=>substr($hasilAllhour['MONTH_DISPLAY'],0,3),'value'=>$hasilAllhour['JML_JAM_BULANAN']/$durasihour*100]);
            else
                array_push($hasil['allhour'],['label'=>substr($hasilAllhour['MONTH_DISPLAY'],0,3),'value'=>0]);

        }
        echo json_encode($hasil);
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
            if($hasilAllentry['JML_ENTRY_BULANAN']>0 && $durasi >0)
                array_push($hasil['allentry'],['label'=>$hasilAllentry['MONTH_DISPLAY'],'value'=>$hasilAllentry['JML_ENTRY_BULANAN']/$durasi*100]);
            else
                array_push($hasil['allentry'],['label'=>$hasilAllentry['MONTH_DISPLAY'],'value'=>0]);

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
            if($hasilAllhour['JML_JAM_BULANAN']>0 && $durasihour >0)
                array_push($hasil['allhour'],['label'=>$hasilAllhour['MONTH_DISPLAY'],'value'=>$hasilAllhour['JML_JAM_BULANAN']/$durasihour*100]);
            else
                array_push($hasil['allhour'],['label'=>$hasilAllhour['MONTH_DISPLAY'],'value'=>0]);

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

        //add field children
        for($i = 0 ; $i < count($data_bu) ; $i++){
            $data_bu[$i]['children'] = null;
        }
        //$fixdata = ['directorat'=>[],'company'=>[],'business_unit'=>[]];


        $tree['list_bu'] = $this->buildTree($data_bu);

        /*
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
        }*/
        echo json_encode($tree);
    }

    private function buildTree(array $elements, $parentId = null) {
        $branch = array();

        foreach ($elements as $element) {
            if ($element['BU_PARENT_ID'] == $parentId) {
                $children = $this->buildTree($elements, $element['BU_ID']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }

        return $branch;
    }

    //https://marvelapp.com/hj9eb56/screen/29382899
    public function r_directoratbu(){
        $bu = $_POST['bu'];
        $tahun = $_POST['tahun'];
        $data =array();
        $completed = 0;
        $in_progress = 0;
        $not_started = 0;
        $jumlah = 0;
        $total_project_value = 0;
        //get business unit info
        $c=$this->M_report->getbu($bu);
        //if directorat
        if ($c['BU_PARENT_ID']=='0') {
            $child=$this->M_report->getbuchild($bu);
            foreach ($child as $ch) {
                $completed = $completed +$this->M_report->Portofolio_completed_Project($ch['BU_ID'],$tahun);
                $in_progress = $in_progress + $this->M_report->Portofolio_Active_Project($ch['BU_ID'],$tahun);
                $not_started = $not_started + $this->M_report->Portofolio_notstarted_Project($ch['BU_ID'],$tahun);
                $jumlah = $jumlah + $this->M_report->Portofolio_Total_Project($ch['BU_ID'],$tahun);
                $total_project_value = $total_project_value + $this->M_report->Portofolio_Total_Project_Value($ch['BU_ID'],$tahun);
            }
            $data['project_dir']['completed'] = $completed;
            $data['project_dir']['in_progress']= $in_progress;
            $data['project_dir']['not_started'] = $not_started;
            $data['project_dir']['jumlah']= $jumlah;
            $data['finance']['total_project_value'] = $total_project_value;
        }
        //if business unit
        else{
            $data['project_dir']['completed'] = $this->M_report->Portofolio_completed_Project($bu,$tahun);
            $data['project_dir']['in_progress']= $this->M_report->Portofolio_Active_Project($bu,$tahun);
            $data['project_dir']['not_started'] = $this->M_report->Portofolio_notstarted_Project($bu,$tahun);
            $data['project_dir']['jumlah']= $this->M_report->Portofolio_Total_Project($bu,$tahun);
            $data['finance']['total_project_value'] = $this->M_report->Portofolio_Total_Project_Value($bu,$tahun);
        }
        print_r(json_encode($data));
    }

    public function chart_directoratbu(){

        $tahun = $this->input->post('thn');
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
            $res['jml_util']=round($this->M_report->getUtilBUYearly($bu,$tahun)/($this->countDuration($tahun."/1/1", date("Y/m/d"))*8) *100/$count_user,2);
        }
        else{
            $res['jml_entry']=round($this->M_report->getEntryBUYearly($bu,$tahun)/$this->countDuration($tahun."/1/1", $tahun."/12/31") *100/$count_user,2);
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
        $res['allentry'] = [];
        $i=1;
        foreach ($allentry as $has) {

            array_push($res['allentry'],[$has['BULAN'],$has['JML_ENTRY_BULANAN']*100/($count_user*$this->getdurationmonth($has['BULAN'],$tahun))]);
            $i++;
        }

        $allhour=$this->M_report->getAllHourBU($bu,$tahun);
        $res['allhour']= [];
        $i=1;
        foreach ($allhour as $hus) {
            $res['allhour'][$i]['bulan']= $hus['BULAN'];
            $res['allhour'][$i]['utilization']=$hus['JML_ENTRY_BULANAN']*100/(8*$count_user*$this->getdurationmonth($has['BULAN'],$tahun));
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

    //https://marvelapp.com/hj9eb56/screen/29382902
    public function r_people(){
        $bu_id = $_POST['BU_ID'];
        $bulan = $_POST['BULAN'];
        $tahun = $_POST['TAHUN'];
        $y=(int)date("Y");
        $m=(int)date("m");

        $datareport=$this->M_report->get_user_bu($bu_id);



        for($i = 0 ; $i <count($datareport);$i++){

            //utilization
            $utilization=$this->M_report->getTotalHour($datareport[$i]['USER_ID'],$bulan,$tahun);

            //entry
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
        $wrap['report_people'] = $datareport;
        echo json_encode($wrap);
    }
    public function r_people_download(){
        $bu_id = $_GET['BU_ID'];
        $bulan = $_GET['BULAN'];
        $tahun = $_GET['TAHUN'];
        $y=(int)date("Y");
        $m=(int)date("m");

        $datareport=$this->M_report->get_user_bu($bu_id);



        for($i = 0 ; $i <count($datareport);$i++){

            //utilization
            $utilization=$this->M_report->getTotalHour($datareport[$i]['USER_ID'],$bulan,$tahun);

            //entry
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
        $wrap['report_people'] = $datareport;
        $this->load->library('excel');

        $this->excel->setActiveSheetIndex(0);
            //name the worksheet
        $this->excel->getActiveSheet()->setTitle('Report Project');
            //set cell A1 content with some text
        $this->excel->getActiveSheet()->setCellValue('A1', 'This is just some text value');


        //$this->excel->getActiveSheet()->fromArray($wrap);

            $filename='Project Report.xls'; //save our workbook as this file name

            header('Content-Type: application/vnd.ms-excel'); //mime type

            header('Content-Disposition: attachment;filename="'.$filename.'"'); //tell browser what's the file name

            header('Cache-Control: max-age=0'); //no cache

            //save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
            //if you want to save it as .XLSX Excel 2007 format

            $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');

            //force user to download the Excel file without writing it to server's HD
            $objWriter->save('php://output');
            echo json_encode($wrap);
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


        $c=$this->M_report->getbu($bu);
        if ($c['BU_PARENT_ID']=='0') {
            $count_user=0;
            $child=$this->M_report->getbuchild($bu);
            $res['jml_entry']=0;
            $count=count($child);
            foreach ($child as $ch) {
                $count_user=$this->M_report->getCountUser($ch['BU_ID']);
                if ($count_user==0) {
                  $count=$count-1;
              }else{
                  if (($tahun==$y)){
                      $res['jml_entry']=$res['jml_entry']+(round($this->M_report->getEntryBUYearly($ch['BU_ID'],$tahun)/$this->countDuration($tahun."/1/1", date("Y/m/d")) *100/$count_user,2));
                  }else{
                      $res['jml_entry']=$res['jml_entry']+(round($this->M_report->getEntryBUYearly($ch['BU_ID'],$tahun)/$this->countDuration($tahun."/1/1", $tahun."/12/31") *100/$count_user,2));
                  }
              }
          }
          $res['jml_entry']=$res['jml_entry']/$count;
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
        $res['allentry']=[];
        foreach ($child as $chs) {
            $allentry=$this->M_report->gettahunanbu($chs['BU_ID'],$tahun);
            $i=1;
            foreach ($allentry as $has) {
                $count_user=$this->M_report->getCountUser($chs['BU_ID']);
                if ($count_user>0) {
                    $monthName = date('M', mktime(0, 0, 0, $i, 10)); // March
                    if($i<=12)
                        array_push($res['allentry'],['label'=>$monthName,'value'=>$res['allentry'][$i][1]+($has['JML_ENTRY_BULANAN']*100/($count_user*$this->getdurationmonth($has['BULAN'],$tahun)))]);
                    $i++;
                }

            }


        }
        for ($v=1; $v<=12 ; $v++) {
            $res['allentry'][$v][1]=$res['allentry'][$v][1]/$count;
        }
    }else{
        $count_user=$this->M_report->getCountUser($bu);
          //echo print_r($tahun);
        $count_user = ($count_user > 0) ? $count_user : 1;

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
              $monthName = date('M', mktime(0, 0, 0, $i, 10)); // March
              array_push($res['allentry'],['label'=>$monthName,'value'=>$has['JML_ENTRY_BULANAN']*100/($count_user*$this->getdurationmonth($has['BULAN'],$tahun))]);

              $i++;
          }
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

    $result["r_entry_bu"] = $res;

    echo json_encode($result,JSON_NUMERIC_CHECK);




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
        $c=$this->M_report->getbu($bu);
        if ($c['BU_PARENT_ID']=='0') {
            $count_user=0;
            $child=$this->M_report->getbuchild($bu);
            $res['jml_util']=0;
            $count=count($child);
            foreach ($child as $ch) {
                $count_user=$this->M_report->getCountUser($ch['BU_ID']);
                if ($count_user==0) {
                  $count=$count-1;
                  $count_user=1;
              }else{
                  if (($tahun==$y)){
                      $res['jml_util']=$res['jml_util']+(round($this->M_report->getUtilBUYearly($ch['BU_ID'],$tahun)/($this->countDuration($tahun."/1/1", date("Y/m/d"))*8) *100/$count_user,2));
                  }
                  else{
                      $res['jml_util']=$res['jml_util']+(round($this->M_report->getUtilBUYearly($ch['BU_ID'],$tahun)/($this->countDuration($tahun."/1/1", $tahun."/12/31")*8) *100/$count_user,2));
                  }
              }
          }
          $res['jml_util']=$res['jml_util']/$count;
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
        $res['allhour']=[];
          // $i=1;
          // foreach ($allhour as $hus) {
          //     $res['allhour'][$i][0]= $hus['BULAN'];
          //     $res['allhour'][$i][1]=$hus['JML_ENTRY_BULANAN']*100/(8*$count_user*$this->getdurationmonth($hus['BULAN'],$tahun));
          //     $i++;
          // }
        foreach ($child as $chs) {
            $allhour=$this->M_report->getAllHourBU($chs['BU_ID'],$tahun);
            $i=1;
            foreach ($allhour as $hus) {
                $count_user=$this->M_report->getCountUser($chs['BU_ID']);
                if ($count_user>0) {
                    $monthName = date('M', mktime(0, 0, 0, $i, 10)); // March
                    array_push($res['allhour'],['label'=>$monthName,'value'=>($hus['JML_ENTRY_BULANAN']*100/(8*$count_user*$this->getdurationmonth($hus['BULAN'],$tahun)))]);

                    $i++;
                }

            }

        }
        for ($v=1; $v<=12 ; $v++) {
            $res['allhour'][$v][1]=$res['allhour'][$v][1]/$count;
        }
    }else {
        $count_user=$this->M_report->getCountUser($bu);
              //echo print_r($tahun);
        if ($count_user==0) {
            $count=$count-1;
            $count_user=1;
        }
        else if (($tahun==$y)){

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
              $monthName = date('M', mktime(0, 0, 0, $i, 10)); // March
              array_push($res['allhour'],['label'=>$monthName,'value'=>($hus['JML_ENTRY_BULANAN']*100/(8*$count_user*$this->getdurationmonth($hus['BULAN'],$tahun)))]);
              $i++;
          }
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
        $res['allhour']=$res['allhour'];

        $result["r_util_bu"] = $res;

        echo json_encode($result,JSON_NUMERIC_CHECK);
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
            order by case when b.bu_code = 'SMS' then 1
            when b.bu_code = 'SSI' then 2
            when b.bu_code = 'SGP' then 3
            else 5
             end DESC");
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
                order by case when b.bu_code = 'SMS' then 1
                when b.bu_code = 'SSI' then 2
                when b.bu_code = 'SGP' then 3
                else 5
                   end DESC");
            $result["r_yearly"][$i] = $query->result();
            $result["r_yearly"][$i]["month_name"] = $month;
        }
        echo json_encode($result);
    }

            //report monthly overview
    public function r_month(){
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
            and type_of_effort in ('1','2')
            and pv!='0'
            and b.BU_CODE !='PROUDS'
            and b.BU_code !='GTS'
            and b.BU_code !='NSM'
            group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id
            order by case when b.bu_code = 'SMS' then 1
            when b.bu_code = 'SSI' then 2
            when b.bu_code = 'SGP' then 3
            else 5
             end DESC");
        $result["r_monthly"] = $query->result();
        $known = array();
        $knownz = array();
        $filtered["r_monthly"] = array_filter($result["r_monthly"], function ($val) use (&$known,&$knownz) {
            $unique = !in_array($val->BU_ALIAS, $knownz);
            array_push($known,$val);

            $knownz[] = $val->BU_ALIAS;
            return $unique;
        });
        $object = new stdClass();
        foreach ($filtered as $key => $value)
        {
            $object->$key = $value;
        }
        $filteredz["r_monthly"] = array($object);
        echo json_encode($filtered);
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
                and type_of_effort in ('1','2')
                and pv!='0'
                and b.BU_CODE !='PROUDS'
                and b.BU_code !='GTS'
                and b.BU_code !='NSM'
                group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id
                order by case when b.bu_code = 'SMS' then 1
                when b.bu_code = 'SSI' then 2
                when b.bu_code = 'SGP' then 3
                else 5
                   end DESC");
            $hasil =$query->result();
            $anu = array("name" => $month);
            $anuz = array("name" => $month);

            for($o=0; $o<count($hasil); $o++)
            {
                $anu[$hasil[$o]->BU_ALIAS]=$hasil[$o]->CPI;
                $anuz[$hasil[$o]->BU_ALIAS]=$hasil[$o]->SPI;
            }

            $result["r_yearly_cpi"][]=$anu;
            $result["r_yearly_spi"][]=$anuz;

                // $result["r_yearly"][]["month_name"] = $month;
        }
        echo json_encode($result);

    }


    public function report_filter(){
        $keyword = $this->input->post('keyword');
        $page = $this->input->post('page');
        $limit =$this->input->post('limit');
        $query ="select project_name,iwo_no,project_status,project_complete as percent,amount,
        customer_name,pm,schedule_status,budget_status,ev,pv,ac,spi,cpi from v_find_project
        where 1=1
        ";
        $i = 0;
        $value = $this->input->post('value');
        $status = $this->input->post('status');
        $schedule = $this->input->post('schedule');
        $budget = $this->input->post('budget');

        if(!empty($value)){
            $valueVal = ["< 1000000000","between 1000000000 and 5000000000","> 5000000000"];
            $query.=" and ( ";
            for($a = 0 ; $a < count($valueVal);$a++){
                if($i == 0){
                        //$query .= " where ";
                    $i++;
                }
                    // elseif($value[$a] == 1 ){
                    //     $query .= " or ";
                    // }
                if($value[$a] == 1 && $a==0){
                    $query .= " amount '".$valueVal[$a]."' ";
                }
                elseif($value[$a] == 1 && $a>0){
                    $query .= " or amount '".$valueVal[$a]."' ";
                }
            }
            $query.=" ) ";
        }if(!empty($status)){
            $valueVal = ["Not Started","In Progress","On Hold","Completed","In Planning","Cancelled"];
            $query.=" and project_status in ( ";
            for($a = 0 ; $a < count($valueVal);$a++){
                if($i == 0){
                        //$query .= " where ";
                    $i++;
                }
                    // elseif($status[$a] == 1 ){
                    //     $query .= " or ";
                    // }
                    // if($status[$a] == 1 && $a==0){
                    //     $query .= " project_status =  '".$valueVal[$a]."'";
                    // }elseif ($status[$a] == 1 && $a>0) {
                    //     $query .= " or project_status =  '".$valueVal[$a]."'";
                    // }
                    // else{
                    //     $query .= " or project_status =  '".$valueVal[$a]."'";
                    // }
                if($status[$a] == 1){
                    $query.=" '".$valueVal[$a]."',";
                }
                if ($a==count($valueVal)-1) {
                    $query=rtrim($query,",");
                }
            }

            $query.=" ) ";
        }if(!empty($schedule)){
            $query.=" and schedule_status in ( ";

            $valueVal = ["Schedule Overrun","On Schedule","Ahead Schedule"];
            for($a = 0 ; $a < count($valueVal);$a++){
                if($i == 0){
                        //$query .= " where ";
                    $i++;
                }
                    // elseif($schedule[$a] == 1 ){
                    //     //echo $c;
                    //     $query .= " or ";
                    // }
                    // if($schedule[$a] == 1&& $a==0){
                    //     $query .= " schedule_status = '".$valueVal[$a]."'";
                    // }
                    // elseif($schedule[$a] == 1&& $a>0){
                    //     $query .= " or schedule_status = '".$valueVal[$a]."'";
                    // }
                if($schedule[$a] == 1){
                    $query.=" '".$valueVal[$a]."',";
                }
                if ($a==count($valueVal)-1) {
                    $query=rtrim($query,",");
                }

            }
            $query.=" ) ";
        }if(!empty($budget)){

            $query.=" and budget_status in ( ";
            $valueVal = ["Over Budget","On Budget","Ahead Budget"];
            for($a = 0 ; $a < count($valueVal);$a++){
                if($i == 0){
                        //$query .= " where ";
                    $i++;
                }
                    // elseif($budget[$a] == 1){
                    //     $query .= " or ";
                    // }
                    // if($budget[$a] == 1&& $a==0){
                    //     $query .= " budget_status = '".$valueVal[$a]."'";
                    // }elseif($budget[$a] == 1&& $a>0){
                    //     $query .= " or budget_status = '".$valueVal[$a]."'";
                    // }
                if($budget[$a] == 1){
                    $query.=" '".$valueVal[$a]."',";
                }
                if ($a==count($valueVal)-1) {
                    $query=rtrim($query,",");
                }

            }

            $query.=" ) ";
        }

            //for search by keyword
        if($keyword != null){
            $query .= "and ( lower(project_name) like lower('$keyword') || '%' or lower(project_name) like '%' || lower('$keyword') or lower(project_name) like '%'|| lower('$keyword') || '%')";
        }



        $query_pagination = $query;
            //for pagination
        ($page == null ? $page = 1: false);
        ($limit == null ? $limit = 5:false);
        $query .= " and rownum between ".(string)($page*$limit-$limit)." and ".(string)($page*$limit);

        $query .= "order by date_created";

            //run query
        $result['project_find'] = $this->db->query($query)->result_array();
        $result['pagenumber'] = ceil($this->db->query($query_pagination)->num_rows()/$limit);

        echo json_encode($result);
    }



    public function report_filter_download(){

        $this->load->library('excel');

        $this->excel->setActiveSheetIndex(0);
            //name the worksheet
        $this->excel->getActiveSheet()->setTitle('Report Project');
            //set cell A1 content with some text
        $this->excel->getActiveSheet()->setCellValue('A1', 'This is just some text value');


        $query ="select project_name,project_status,project_complete as percent,amount,
        customer_name,pm,schedule_status,budget_status,ev,pv,ac,spi,cpi from v_find_project
        where 1=1";
        $i = 0;

        $keyword = $this->input->post('keyword');
        $value = $this->input->post('value');
        $status = $this->input->post('status');
        $schedule = $this->input->post('schedule');
        $budget = $this->input->post('budget');

        if(!empty($value)){
            $valueVal = ["< 1000000000","between 1000000000 and 5000000000","> 5000000000"];
            $query.=" and ( ";
            for($a = 0 ; $a < count($valueVal);$a++){
                if($i == 0){
                        //$query .= " where ";
                    $i++;
                }
                    // elseif($value[$a] == 1 ){
                    //     $query .= " or ";
                    // }
                if($value[$a] == 1 && $a==0){
                    $query .= " amount '".$valueVal[$a]."' ";
                }
                elseif($value[$a] == 1 && $a>0){
                    $query .= " or amount '".$valueVal[$a]."' ";
                }
            }
            $query.=" ) ";
        }if(!empty($status)){
            $valueVal = ["Not Started","In Progress","On Hold","Completed","In Planning","Cancelled"];
            $query.=" and project_status in ( ";
            for($a = 0 ; $a < count($valueVal);$a++){
                if($i == 0){
                        //$query .= " where ";
                    $i++;
                }
                    // elseif($status[$a] == 1 ){
                    //     $query .= " or ";
                    // }
                    // if($status[$a] == 1 && $a==0){
                    //     $query .= " project_status =  '".$valueVal[$a]."'";
                    // }elseif ($status[$a] == 1 && $a>0) {
                    //     $query .= " or project_status =  '".$valueVal[$a]."'";
                    // }
                    // else{
                    //     $query .= " or project_status =  '".$valueVal[$a]."'";
                    // }
                if($status[$a] == 1){
                    $query.=" '".$valueVal[$a]."',";
                }
                if ($a==count($valueVal)-1) {
                    $query=rtrim($query,",");
                }
            }

            $query.=" ) ";
        }if(!empty($schedule)){
            $query.=" and schedule_status in ( ";

            $valueVal = ["Schedule Overrun","On Schedule","Ahead Schedule"];
            for($a = 0 ; $a < count($valueVal);$a++){
                if($i == 0){
                        //$query .= " where ";
                    $i++;
                }
                    // elseif($schedule[$a] == 1 ){
                    //     //echo $c;
                    //     $query .= " or ";
                    // }
                    // if($schedule[$a] == 1&& $a==0){
                    //     $query .= " schedule_status = '".$valueVal[$a]."'";
                    // }
                    // elseif($schedule[$a] == 1&& $a>0){
                    //     $query .= " or schedule_status = '".$valueVal[$a]."'";
                    // }
                if($schedule[$a] == 1){
                    $query.=" '".$valueVal[$a]."',";
                }
                if ($a==count($valueVal)-1) {
                    $query=rtrim($query,",");
                }

            }
            $query.=" ) ";
        }if(!empty($budget)){

            $query.=" and budget_status in ( ";
            $valueVal = ["Over Budget","On Budget","Ahead Budget"];
            for($a = 0 ; $a < count($valueVal);$a++){
                if($i == 0){
                        //$query .= " where ";
                    $i++;
                }
                    // elseif($budget[$a] == 1){
                    //     $query .= " or ";
                    // }
                    // if($budget[$a] == 1&& $a==0){
                    //     $query .= " budget_status = '".$valueVal[$a]."'";
                    // }elseif($budget[$a] == 1&& $a>0){
                    //     $query .= " or budget_status = '".$valueVal[$a]."'";
                    // }
                if($budget[$a] == 1){
                    $query.=" '".$valueVal[$a]."',";
                }
                if ($a==count($valueVal)-1) {
                    $query=rtrim($query,",");
                }

            }

            $query.=" ) ";
        }

            //for search by keyword
        if($keyword != null){
            $query .= "and ( lower(project_name) like lower('$keyword') || '%' or lower(project_name) like '%' || lower('$keyword') or lower(project_name) like '%'|| lower('$keyword') || '%')";
        }


            //echo $query.$cond;
        $p =$this->db->query($query)->result_array();

            // read data to active sheet
        $this->excel->getActiveSheet()->fromArray($p);

            $filename='Project Report.xls'; //save our workbook as this file name

            header('Content-Type: application/vnd.ms-excel'); //mime type

            header('Content-Disposition: attachment;filename="'.$filename.'"'); //tell browser what's the file name

            header('Cache-Control: max-age=0'); //no cache

            //save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
            //if you want to save it as .XLSX Excel 2007 format

            $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');

            //force user to download the Excel file without writing it to server's HD
            $objWriter->save('php://output');


        }


}
