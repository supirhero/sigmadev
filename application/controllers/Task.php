<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Task extends CI_Controller
{

    private $datajson = [];
    function __construct()
    {
        parent::__construct();
        error_reporting(E_ALL & ~E_NOTICE);

        $this->load->model('M_detail_project');
        $this->load->model('M_session');

        //TOKEN LOGIN CHECKER
        if(isset($_GET['token'])){
            $datauser["data"] = $this->M_session->GetDataUser($_GET['token']);

            $decoded_user_data =$datauser;
            //    print_r($decoded_user_data);
            $this->datajson['token'] = $_GET['token'];
        }
        elseif(isset($_SERVER['HTTP_TOKEN'])){
            $decoded_user_data = $this->M_session->GetDataUser($_SERVER['HTTP_TOKEN']);
            $this->datajson['token'] = $_SERVER['HTTP_TOKEN'];
        }
        else{
            $error['error']="Login First!";
            echo json_encode($error);
            die();
        }
        //if login success
        if(count($decoded_user_data) > 0){
            //get user data from token
            //for login bypass ,this algorithm is not used
            //$this->datajson['userdata'] = (array)$decoded_user_data['data'];
            //this code below for login bypass
            $this->datajson['userdata'] = $decoded_user_data;
        }
        //if login fail
        else {
            $returndata['login_error'] = 'Login Failed';
            echo json_encode($returndata);
            die();
        }

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
        $result['workplan'] = $result[$id_project.'.0'];
        unset($result[$id_project.'.0']);
        echo json_encode($result);


        //echo var_dump($workplan);
    }



    //Create Task
    function createTask(){
        $project_id   = $this->input->post("PROJECT_ID");

        $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;
        if($statusProject == 'On Hold'){
            //wbs id same with project id
            $data['WBS_NAME'] = $this->input->post("WBS_NAME");
            $data['WBS_ID'] = $project_id;
            $data['WBS_PARENT_ID'] = $this->input->post("WBS_PARENT_ID");
            $data['START_DATE']   = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
            $data['FINISH_DATE']  ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";


            // insert into wbs and get new ID
            $newid = $this->M_detail_project->insertWBSTemp($data,$project_id);
        }
        else{
            //wbs id same with project id
            $data['WBS_NAME'] = $this->input->post("WBS_NAME");
            $data['WBS_ID'] = $project_id;
            $data['WBS_PARENT_ID'] = $this->input->post("WBS_PARENT_ID");
            $data['START_DATE']   = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
            $data['FINISH_DATE']  ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";

            // insert into wbs and get new ID
            $newid = $this->M_detail_project->insertWBS($data,$project_id);

            $WP_ID= $this->M_detail_project->getMaxWPID();
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
        }

        $returndata['status'] = "success";
        echo json_encode($returndata);
    }

    //EDIT TASK
    function editTask_view($wbs_id)
    {
        $project_id = explode(".",$wbs_id);
        $query = $this->db->query("select * from wbs where WBS_ID='".$wbs_id."'");
        $data['detail_task'] = $query->result_array();
        $data['parent']=$this->db->query("select wbs_id, wbs_name from wbs where PROJECT_ID='".$project_id[0]."' connect by  wbs_parent_id= prior wbs_id start with wbs_id='".$project_id[0].".0' order siblings by wbs_parent_id")->result_array();
        echo json_encode($data);
    }

    function editTask_action(){


        $project_id   = $this->input->post("PROJECT_ID");

        $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;
        if($statusProject == 'On Hold'){
            $wbs=$this->input->post("WBS_ID");
            $this->M_detail_project->Edit_WBSTemp(
                $_POST["WBS_ID"],
                $_POST["WBS_PARENT_ID"],
                $_POST["PROJECT_ID"],
                $_POST["WBS_NAME"],
                $_POST['START_DATE'],
                $_POST['FINISH_DATE']
            );
        }
        else{
            $wbs=$this->input->post("WBS_ID");
            $this->M_detail_project->Edit_WBS(
                $_POST["WBS_ID"],
                $_POST["WBS_PARENT_ID"],
                $_POST["PROJECT_ID"],
                $_POST["WBS_NAME"],
                $_POST['START_DATE'],
                $_POST['FINISH_DATE']
            );
            //$this->M_detail_project->insertWBS($data,$project_id);
            //$WP_ID= $this->M_detail_project->getMaxWPID();
            //$RP_ID= $this->M_detail_project->getMaxRPID();
            //$this->M_detail_project->insertWBSPool($data,$RP_ID,$WP_ID,$project_id);
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
        }

    }

    //delete task
    public function deleteTask()
    {

        $id = $_POST['WBS_ID'];
        $wbs_id = $_POST['WBS_ID'];
        $project_id = $this->M_detail_project->getProjectTask($id);

        $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;
        if($statusProject == 'On Hold'){
            $this->M_detail_project->updateProgressDeleteTaskTemp($wbs_id);
        }
        else{
            //$this->M_detail_project->deleteWBSID($id);
            //$this->M_detail_project->deleteWBSPoolID($id);
            $this->M_detail_project->updateProgressDeleteTask($wbs_id);
        }

        $returndata['status'] = "success";
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

        $project_id = explode(".",$_POST['WBS_ID']);
        $project_id = $project_id[0];
        $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;

        if($statusProject == 'On Hold'){
            $this->M_detail_project->removeAssignementTemp();
        }
        elseif($statusProject['In Progress']){
            $this->M_detail_project->removeAssignement();

            //send email
            $email=$this->input->post('EMAIL');
            $user_name=$this->input->post('NAME');
            $wbs_name=$this->input->post('WBS_NAME');
            //$this->sendVerificationremoveMember($email,$user_name,$wbs_name);

            //return
        }
        $data['status'] = 'success';
        echo json_encode($data);
    }

    //Assign task to project member
    public function assignTaskMemberProject(){

        $project_id = explode(".",$_POST['WBS_ID']);
        $project_id = $project_id[0];
        $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;

        if($statusProject == 'On Hold'){
            $this->M_detail_project->postAssignmentTemp();
        }
        elseif($statusProject['In Progress']){

            //assign process
            $this->M_detail_project->postAssignment();

            //send email
            $wbs=$this->input->post('WBS_ID');
            $email=$this->input->post('EMAIL');
            $user_name=$this->input->post('NAME');
            $wbs_name=$this->input->post('WBS_NAME');
            $projectid = $this->M_detail_project->getProject_Id($wbs);
            //$this->sendVerificationassignMember($email,$user_name,$wbs_name,$projectid);
        }
        //return
        $data['status'] = 'success';
        echo json_encode($data);

    }

    //Email information remove user from task
    private function sendVerificationremoveMember($email,$user_name,$wbs_name){

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
        //$this->email->to($email);
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject('Deleting Assign From Task');
        $this->email->message("<!DOCTYPE html>
  <html>
  <head>
  <meta name='viewport' content='width=device-width' />
  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
  <title>Remove Member</title>

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
  table.footer-wrap { width: 100%;  clear:both!important;
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
  <img src='cid:".$cid_logo."' alt='logo Telkomsigma' />
  <h2>Hi ,</h3>
  <br/>
  <h4>User ".$user_name."  You are has removed from task ".$wbs_name."</h4>
  <br>

  <br/>
  <p style='text-align: left'>Having Trouble ? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
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
            echo "sent ".$this->email->print_debugger();
        }

    }

    //Email information add user to task
    private function sendVerificationassignMember($email,$user_name,$wbs_name,$projectid){


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
        //$this->email->to($email);
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject('Assign Member to Task');
        $this->email->message("<!DOCTYPE html>
  <html>
  <head>
  <meta name='viewport' content='width=device-width' />
  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
  <title>Remove Member</title>

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
  table.footer-wrap { width: 100%;  clear:both!important;
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
  <img src='cid:".$cid_logo."' alt='logo Telkomsigma' />
  <h2>Hi ,</h3>
  <br/>
  <h4>User ".$user_name." You are has Assigned on Project task ".$wbs_name." </h4>
  <br>
  <a href = '".base_url()."Detail_Project/view/".$projectid."'>Click Here</a>
  <br/>
  <p style='text-align: left'>Having Trouble ? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
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
            echo "sent ".$this->email->print_debugger();
        }
    }


}