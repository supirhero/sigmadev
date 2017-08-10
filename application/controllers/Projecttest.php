<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Projecttest extends CI_Controller
{
    public $datajson = array();
    function __construct()
    {
        parent::__construct();
        $this->load->model('M_project');
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

    function index(){

    }

    /*START ADD PROJECT*/
    public function addProject_view(){
        $code = $this->M_project->getBUCode($this->uri->segment(3));

        //get bussines unit based on uri segment
        $data['business_unit'] = $this->M_business->getData($this->uri->segment(3));
        //get all iwo from api
        @$json = file_get_contents('http://180.250.18.227/api/index.php/mis/iwo_by_bu_code/' . $code->BU_CODE);
        $data['IWO'] = array();
        $data['IWO'] = json_decode($json, true);
        if (empty($data['IWO'])) {
            @$json = file_get_contents('http://180.250.18.227/api/index.php/mis/iwo_by_bu_alias/' . $code->BU_CODE);
            $data['IWO'] = array();
            $data['IWO'] = json_decode($json, true);
        }

        //get pm
        $data['project_manager'] = $this->M_project->getPM($this->uri->segment(3));

        echo json_encode($data);
    }
    //Check if iwo already used
    public function checkiwoused(){
        $iwo=$this->input->post('IWO_NO');
        $returndata['jumlah'] = $this->M_project->verifyIWO($iwo);
        echo json_encode($returndata);
    }
    //checking iwo when IWO selected at "Create Project" page
    public function check() {
        $IWO = $this->input->post("IWO_NO");
        //$IWO="P-1608SCC-TSEL0561";
        $res = $this->M_project->checkIWO($IWO);
        print_r($res);
    }
    //checking Account Manager from iwo number
    public function checkAM() {
        $am = $this->input->post("AM_ID");
        // $bu='14';
        //  $am='S200804071';
        // $json = file_get_contents('http://10.210.20.2/api/index.php/mis/customer/' . $cust_id);
        $data=$this->M_project->getAM($am);
        //  echo $data;
        //$data=$this->M_project->getCustomer($cust_id);
        //$data = json_decode($json, true);
        $returndata['username'] = $data[0]['USER_NAME'];
        echo json_encode($returndata);
    }
    //checking Customer from iwo number
    public function checkCustomer() {
        $cust_id = $this->input->post("CUST_ID");
        //$cust_id=41000004;
        $json = file_get_contents('http://180.250.18.227/api/index.php/mis/customer/' . $cust_id);
        //$data=$this->M_project->getCustomer($cust_id);
        $data = json_decode($json, true);
        $returndata['customer_name'] = $data[0]['CUSTOMER_NAME'];
        echo json_encode($returndata);
    }
    //checking project type id
    public function checkProjectType(){
        $data = $this->input->post('PROJECT_TYPE_ID');
        $res = $this->M_project->getProjectCat($data);
        foreach ($res as $r) {
            echo '<option value="' . $r->ID .'">' . $r->CATEGORY . '</option>';
        }
    }


    //add project if verified
    public function addProject_acion(){
        $test=$this->M_project->addProject($this.$this->datajson['userdata']);
        $SCHEDULE_START = $this->input->post('START');
        $SCHEDULE_END = $this->input->post('END');
        $dur=$this->countDurationAll($SCHEDULE_START,$SCHEDULE_END);
        $this->M_project->addProjectWBS($test,$dur);
    }
    private function countDurationAll($start_date, $end_date) {
        if (empty($start_date)) {
            $start_date=date('Y-m-d');
        }
        if (empty($end_date)) {
            $end_date=date('Y-m-d');
        }
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = $end->diff($start);
        $days = $interval->days;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        return $days;
    }

    /*END ADD PROJECT*/

    /*Start Edit Project*/
    public function editProject_view(){
        $data['project']=$this->M_project->getProject($this->uri->segment(3));
        $data['bu_id']=$this->M_project->getProjectID($this->uri->segment(3));
        $code = $this->M_project->getBUCode($this->uri->segment(3));
        $data['name'] = $this->M_business->getData($data['bu_id']);
        $data['project_type'] = $this->M_project->getProjectType();
        $code = $this->M_project->getBUCodeByProjectID($this->uri->segment(3));

        @$json = file_get_contents('http://180.250.18.227/api/index.php/mis/iwo_by_bu_code/' . $code->BU_CODE);
        $data['IWO'] = array();
        $data['IWO'] = json_decode($json, true);

        if (empty($data['IWO'])) {
            @$json = file_get_contents('http://180.250.18.227/api/index.php/mis/iwo_by_bu_alias/' . $code->BU_CODE);
            $data['IWO'] = array();
            $data['IWO'] = json_decode($json, true);
        }
        echo json_encode($data);
    }

    function editProject_Action(){
        $id=$this->uri->segment(3);
        $this->M_project->update($id);
    }


}