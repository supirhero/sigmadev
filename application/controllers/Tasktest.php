<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Tasktest extends CI_Controller
{

    private $datajson = [];
    function __construct()
    {
        parent::__construct();
        $this->load->model('M_detail_project');
        $this->load->model('M_session');

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
    /*START TASK MANAJEMENT*/
    //Task View
    function workplan_view(){
        $id_project = $this->uri->segment(3);
        $workplan=$this->M_detail_project->selectWBS($id_project);

        //$created_array = $this->buildTree($workplan);

        //built tree
        foreach($workplan as $row) {
            $row['children'] = array();
            $vn = "row" . $row['WBS_ID'];
            ${$vn} = $row;
            if(!is_null($row['WBS_PARENT_ID'])) {
                $vp = "parent" . $row['WBS_PARENT_ID'];
                if(isset($data[$row['WBS_PARENT_ID']])) {
                    ${$vp} = $data[$row['WBS_PARENT_ID']];
                }
                else {
                    ${$vp} = array('n_id' => $row['WBS_PARENT_ID'], 'WBS_PARENT_ID' => null, 'WBS_PARENT_ID' => array());
                    $data[$row['WBS_PARENT_ID']] = &${$vp};
                }
                ${$vp}['children'][] = &${$vn};
                $data[$row['WBS_PARENT_ID']] = ${$vp};
            }
            $data[$row['WBS_ID']] = &${$vn};
        }

        $result = array_filter($data, function($elem) { return is_null($elem['WBS_PARENT_ID']); });
        echo json_encode($result);


        //echo var_dump($workplan);
    }



    //Create Task
    function createTask(){
        $project_id   = $this->input->post("PROJECT_ID");

        //wbs id same with project id
        $data['WBS_NAME'] = $this->input->post("WBS_NAME");
        $data['WBS_ID'] = $project_id;
        $data['WBS_PARENT_ID'] = $this->input->post("WBS_PARENT_ID");
        $data['START_DATE']       = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
        $data['FINISH_DATE']      ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";

        // insert into wbs and get new ID
        $newid = $this->M_detail_project->insertWBS($data,$project_id);

        //get new wbs_pool id
        $WP_ID= $this->M_detail_project->getMaxWPID();

        //get new resource_pool id
        $RP_ID= $this->M_detail_project->getMaxRPID();
        //get all wbs data from new wbs
        $selWBS=$this->M_detail_project->getWBSselected($newid);
        $allParent = $this->M_detail_project->getAllParentWBS($selWBS->WBS_ID);

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
        $data['status'] = "Success add Task";
        echo json_encode($data['status']);
    }

    //EDIT TASK
    function editTask_view($wbs_id)
    {
        $query = $this->db->query("select * from wbs where WBS_ID='".$wbs_id."'");
        $data['hasil'] = $query->result_array();
        echo json_encode($data);
    }

    function editTask_action(){
        $wbs=$this->input->post("WBS_ID");
        if(isset($_POST["submit"])) {
            $this->M_detail_project->Edit_WBS(
                $_POST["WBS_ID"],
                $_POST["WBS_PARENT_ID"],
                $_POST["PROJECT_ID"],
                $_POST["WBS_NAME"],
                $_POST["WBS_DESC"],
                $_POST["PRIORITY"],
                $_POST["CALCULATION_TYPE"],
                $_POST['START_DATE'],
                $_POST['FINISH_DATE'],
                $_POST["DURATION"],
                $_POST["WORK"],
                $_POST["MILESTONE"],
                $_POST["WORK_COMPLETE"],
                $_POST["WORK_PERCENT_COMPLETE"]
            );
            $project_id = $this->input->post("PROJECT_ID");
            //$this->M_detail_project->insertWBS($data,$project_id);
            //$WP_ID= $this->M_detail_project->getMaxWPID();
            //$RP_ID= $this->M_detail_project->getMaxRPID();
            //$this->M_detail_project->insertWBSPool($data,$RP_ID,$WP_ID,$project_id);
            $this->session->set_flashdata("pesan", "<div class=\"alert alert-success\" id=\"alert\"><i class=\"glyphicon glyphicon-ok\"></i> Data berhasil disimpan</div><script>
    setTimeout(function(){

      $('#alert').remove();
      location.reload();
    },1500);
    </script>");
            $project_id = $this->input->post("PROJECT_ID");
            $r = '/Detail_Project/view/'.$project_id.'#tab2';
            //echo $r;
            $selWBS=$this->getSelectedWBS($wbs);
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
            redirect($r);
            //echo $dateStartWBS.$dateEndWBS;

        }else{
            $this->session->set_flashdata("pesan", "<div class=\"alert alert-warning\" id=\"alert\"><i class=\"glyphicon glyphicon-ok\"></i> Data gagal disimpan</div><script>
    setTimeout(function(){

      $('#alert').remove();
      location.reload();
    },1500);
    </script>");
            $project_id = $this->input->post("PROJECT_ID");
            $r = '/Detail_Project/view/'.$project_id;

        }
    }

    //delete task
    public function deleteTask()
    {
        $id = $this->uri->segment(3);
        $wbs_id = $this->uri->segment(3);
        $project_id = $this->M_detail_project->getProjectTask($id);
        //$this->M_detail_project->deleteWBSID($id);
        //$this->M_detail_project->deleteWBSPoolID($id);
        $this->M_detail_project->updateProgressDeleteTask($wbs_id);

        $returndata['status'] = "success delete task";
        echo json_encode($returndata);
    }

    //Update Task Complete Percent
    public function editTaskPercent(){
        $data['WBS_ID']=$this->input->post("WBS_ID");
        $data['PROJECT_ID']=$this->input->post("PROJECT_ID");
        $data['WORK_PERCENT_COMPLETE']=$this->input->post("WORK_PERCENT_COMPLETE");

        //data di null kan , supaya input di modal berhasil
        $data['DESCRIPTION']="";
        $data['DATE']=date("d/m/Y");
        $data['USER_ID']=$this->datajson['userdata']['USER_ID'];
        $this->M_detail_project->UpdatePercentWBS($data);

        $returndata['status'] = "success";
        echo json_encode($returndata);
    }

    //View Edit task member project
    public function assignTaskMember_view(){
        $project=$this->input->post('PROJECT_ID');
        $wbs_id=$this->input->post('WBS_ID');
        $data['task_name'] = $this->M_detail_project->getWBSselected($wbs_id)->WBS_NAME;
        $data['available_to_assign'] = $this->M_detail_project->getWBSAvailableUser($project,$wbs_id);
        $data['currently_assigned']=$this->M_detail_project->getWBSselectedUser($project,$wbs_id);
        echo json_encode($data);
    }

    //Remove task from task member
    public function removeTaskMemberProject(){
        $this->M_detail_project->removeAssignement();

        //send email
        $email=$this->input->post('EMAIL');
        $user_name=$this->input->post('NAME');
        $wbs_name=$this->input->post('WBS_NAME');
        //$this->sendVerificationremoveMember($email,$user_name,$wbs_name);

        //return
        $data['status'] = 'success';
        echo json_encode($data);
    }

    //Assign task to project member
    public function assignTaskMemberProject(){
        //assign process
        $this->M_detail_project->postAssignment();

        //send email
        $wbs=$this->input->post('WBS_ID');
        $email=$this->input->post('EMAIL');
        $user_name=$this->input->post('NAME');
        $wbs_name=$this->input->post('WBS_NAME');
        $projectid = $this->M_detail_project->getProject_Id($wbs);
        $this->sendVerificationassignMember($email,$user_name,$wbs_name,$projectid);

        //return
        $data['status'] = 'success';
        echo json_encode($data);

    }

}