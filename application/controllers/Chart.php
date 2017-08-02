<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Dhtmlx\Connector\GanttConnector;

class Chart extends CI_Controller {
    function __construct()
    {
        parent::__construct();
        $this->load->model('M_detail_project');
        $this->load->model('M_project');
        $this->load->model('M_invite');
        $this->load->model('M_issue');
        $this->load->model('M_Member_Activity');
        $this->load->helper(array('form', 'url'));
    }
    function gantt($project_id)
    {
        $list=$this->M_project->getWBS($project_id);

        /// end here
        foreach($list as $l){
            $wbs[]=array('text'=>$l['TEXT'],'id'=>$l['ID'],'parent'=>$l['PARENT'],'start_date'=>date("Y-m-d",strtotime($l['START_DATE'])),'duration'=>$l['DURATION'],'progress'=>$l['PROGRESS']);
        }
        echo json_encode($wbs);

    }
    function testzzz()
    {
       // $list=$this->M_project->v_ac_project();


        //print_r($list);

    }
    function test()
    {
        $list=$this->M_Member_Activity->selectTimesheet("8790852");

print_r($list);
    }



}

?>