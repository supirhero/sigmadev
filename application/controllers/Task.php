<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Task extends CI_Controller
{

    private $datajson = [];
    private $wp_modif = false;
    function __construct()
    {
        parent::__construct();
        $this->datajson['privilege'] = ['master_data_access'=>false,
            'manage_role_access'=>false,
            'create_project'=>false,
            'bu_access' => false,
            'bu_invite_member'=>false,
            'report_overview'=>false,
            'report_bu_directorat'=>false,
            'report_bu_teammember'=>true,
            'report_find_project'=>false,
            'edit_project'=>false,
            'timesheet_approval'=>false,
            'workplan_modification'=>false,
            'project_member'=>false,
            'upload_doc'=>false,
            'upload_issue'=>false,
            'approve_rebaseline'=>false,
            'edit_task_percent'=>false
        ];
        error_reporting(E_ALL & ~E_NOTICE);

        $this->load->model('M_detail_project');
        $this->load->model('M_session');
        $this->load->helper('file');

        //TOKEN LOGIN CHECKER
        $datauser = $this->M_session->GetDataUser();
        //    print_r($decoded_user_data);
        $this->datajson['token'] = $datauser["token"];

        if(isset($datauser["error"]))
        {
            $this->output->set_status_header($datauser["status"]);
            echo json_encode($datauser);
            die();
        }
        $decoded_user_data = array_change_key_case($datauser["data"], CASE_UPPER);
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
        /*================================================================================*/
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
                                    order by al.type asc")->result_array();
        $profile_id = $this->datajson['userdata']['PROF_ID'];
        foreach($privilege as $priv){
            $will_die = 0;
            //jika akses url ada di dalam db
            if($priv['ACCESS_URL'] == $url_dest){
                //jika akses tipe nya business
                if($priv['TYPE'] == 'BUSINESS'){
                    if($priv['PRIVILEGE'] == 'all_bu'){
                        $this->allowed_bu ="'BAS','TSC','TMS','FNB','CIB','INS','MSS','CIA','SGP','SSI','SMS'";
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
                            $bu_id_all= $this->db->query("select bu_id from p_bu")->result_array();
                            foreach ($bu_id_all as $buid){
                                $directorat_bu[] = $buid['BU_ID'];
                            }
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
                                if($url_dest == 'project/addproject_acion'){
                                    $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['BU']."'")->row()->BU_ID;
                                }
                                elseif ($url_dest == 'project/addproject_view'){
                                    $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
                                }
                                break;
                            case '3':
                                $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
                                break;
                            case '4':
                                $bu_id = $this->input->post('BU_ID');
                                break;
                            case '5':
                                $this->allowed_bu ="'".$this->db->query("select bu_code from p_bu where bu_id = '$user_bu'")->row()->BU_CODE."'";
                                $bu_id = 'masuk';
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
                            case '16':
                                $bu_id = $this->db->query("select pbu.bu_id from projects p 
                                                           join p_bu pbu
                                                           on pbu.bu_code = p.bu_code
                                                           where p.project_id = '".$this->input->post('project_id')."'
                                                           ")->row()->BU_ID;
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
                    if($will_die ==1){
                        $user_bu_name = $this->db->query("select bu_name from p_bu where bu_id = '".$this->datajson['userdata']['BU_ID']."'")->row()->BU_NAME;
                        $acces_bu_name = $this->db->query("select bu_name from p_bu where bu_id = '".$bu_id."'")->row()->BU_NAME;
                        $this->output->set_status_header(403);
                        $returndata['status'] = 'failed';
                        $returndata['message'] = "Anda tidak bisa mengakses feature yang ada di business unit ini. Business unit anda : '$user_bu_name' dan business unit yang anda akan akses : '$acces_bu_name'";
                        echo json_encode($returndata);
                        die;
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
                                $user_id = $this->datajson['userdata']['USER_ID'];
                                $gpl = $this->db->query("select project_id from projects where pm_id ='$user_id'")->result_array();

                                $granted_project_list = null;
                                $granted_project_list = [];
                                $granted_project_list[]= null ;
                                foreach ($gpl as $gg){
                                    $granted_project_list[] = $gg['PROJECT_ID'];
                                }
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
                                    case ($url_dest == 'project/rebaseline') || ( $url_dest == 'project/baseline'):
                                        $project_id_req = $this->input->post("project_id");
                                        break;
                                }

                                if(!in_array($project_id_req,$granted_project_list)){
                                    $this->output->set_status_header(403);
                                    $returndata['status'] = 'failed';
                                    $returndata['message'] = 'Hanya Project Manager dari project yang bersangkutan yang berhak memodifikasi workplan';
                                    echo json_encode($returndata);
                                    die;
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
                            case '15':
                                $project_id_req=$this->input->post("PROJECT_ID");
                                break;
                        }

                        if(!in_array($project_id_req,$granted_project_list)){
                            $will_die = 1;
                        }
                    }
                    else{
                        $will_die = 1;
                    }
                    if($will_die ==1){
                        $this->output->set_status_header(403);
                        $returndata['status'] = 'failed';
                        $returndata['message'] = 'Anda tidak bisa mengakses feature ini';
                        echo json_encode($returndata);
                        die;
                    }
                }
                else{
                    $will_die = 1;
                }
                if($will_die ==1){
                    $this->output->set_status_header(403);
                    $returndata['status'] = 'failed';
                    $returndata['message'] = 'Anda tidak bisa mengakses feature ini';
                    echo json_encode($returndata);
                    die;
                }
            }
        }
        /*===============================================================================*/

        /*==============================================================================*/
        /* FOR PRIVILEGE INTEGRATION */
        $user_privilege = $this->db->query("select a.access_name,b.access_id,b.privilege
                                            from access_list a join profile_access_list b
                                            on a.access_id = b.access_id
                                            where b.profile_id = '".$this->datajson['userdata']['PROF_ID']."' order by a.access_id asc ")->result_array();
        if($user_privilege[0]['PRIVILEGE'] == 'all_bu'){
            $this->datajson['privilege']['master_data_access']=true;
            $this->datajson['privilege']['manage_role_access']=true;
        }
        if($user_privilege[1]['PRIVILEGE'] == 'all_bu' || $user_privilege[2]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['create_project']=true;
        }
        if($user_privilege[2]['PRIVILEGE'] == 'all_bu' || $user_privilege[2]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['bu_access']=true;
        }
        if($user_privilege[3]['PRIVILEGE'] == 'all_bu' || $user_privilege[3]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['bu_invite_member']=true;
        }
        if($user_privilege[4]['PRIVILEGE'] == 'all_bu' || $user_privilege[4]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['report_overview']=true;
        }
        if($user_privilege[5]['PRIVILEGE'] == 'all_bu' || $user_privilege[5]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['report_bu_directorat']=true;
        }
        if($user_privilege[6]['PRIVILEGE'] == 'all_bu' || $user_privilege[5]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['report_bu_teammember']=true;
        }
        if($user_privilege[7]['PRIVILEGE'] == 'all_bu' || $user_privilege[7]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['report_find_project']=true;
        }
        if($user_privilege[8]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['edit_project']=true;
        }
        if($user_privilege[9]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['timesheet_approval']=true;
        }
        if($user_privilege[10]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['workplan_modification']=true;
            $this->wp_modif = true;
        }
        if($user_privilege[11]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['project_member']=true;
        }
        if($user_privilege[12]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['upload_doc']=true;
        }
        if($user_privilege[13]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['upload_issue']=true;
        }
        if($user_privilege[14]['PRIVILEGE'] == 'can'){
            $this->datajson['privilege']['edit_task_percent']=true;
        }
        if($user_privilege[15]['PRIVILEGE'] == 'all_bu' || $user_privilege[15]['PRIVILEGE'] == 'only_bu'){
            $this->datajson['privilege']['approve_rebaseline']=true;
        }

    }

    /*START TASK MANAJEMENT*/
    //Task View
    function workplan_view(){
        $id_project = $this->uri->segment(3);
        $status_project = $this->db->query("select lower(project_status) as project_status from projects where project_id = '$id_project'")->row()->PROJECT_STATUS;

        if($status_project == 'in progress' && $this->wp_modif){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$id_project'")->row()->RH_ID;
            $workplan=$this->db->query("select SUBSTR(WBS_ID, INSTR(wbs_id, '.')+1) as orde,
                                          WBS_ID,WBS_PARENT_ID,PROJECT_ID,
                                          WBS_NAME,WBS_DESC,PRIORITY,CALCULATION_TYPE,START_DATE,FINISH_DATE,
                                          DURATION,WORK,WORK_COMPLETE,WORK_PERCENT_COMPLETE,PROGRESS_WBS,RESOURCE_WBS,rebaseline,
                                          connect_by_isleaf as LEAF,LEVEL from (
                                            select WBS_ID,WBS_PARENT_ID,PROJECT_ID,
                                                  WBS_NAME,WBS_DESC,PRIORITY,CALCULATION_TYPE,START_DATE,FINISH_DATE,
                                                  DURATION,WORK,WORK_COMPLETE,WORK_PERCENT_COMPLETE,PROGRESS_WBS,RESOURCE_WBS,'no' as rebaseline
                                            from wbs
                                            union
                                            select WBS_ID,WBS_PARENT_ID,PROJECT_ID,
                                                  WBS_NAME,WBS_DESC,PRIORITY,CALCULATION_TYPE,START_DATE,FINISH_DATE,
                                                  DURATION,WORK,WORK_COMPLETE,WORK_PERCENT_COMPLETE,PROGRESS_WBS,RESOURCE_WBS,'yes' as rebaseline
                                             from temporary_edit_wbs
                                              where action = 'create'
                                          ) connect by  wbs_parent_id = prior wbs_id
                                          start with wbs_id='$id_project.0'
                                          order siblings by regexp_substr(orde, '^\D*') nulls first,
                                          to_number(regexp_substr(orde, '\d+'))")->result_array();
            $workplan_wp = [];
            $rebaseline = $this->db->query("select wbs_id,wbs_parent_id,project_id,wbs_name,start_date,
                    finish_date as end_date, duration,work,work_complete as work_total,
                    work_percent_complete, 'yes' as rebaseline, action from temporary_edit_wbs
                    where project_id = '$id_project'")->result_array();
            $rebaseline_wp = [];
            $findIndex = [];

            //store task id in workplan
            foreach($workplan as &$wp){
                $workplan_wp[] = (string)$wp['WBS_ID'];
                $wp['status']='none';
                $wp['index_rebaseline'] = 'none';
            }

            //store task id inside rebaseline
            foreach ($rebaseline as $rwp){
                $rebaseline_wp[] = (string)$rwp['WBS_ID'];
            }

            //find index task that need rebaseline
            foreach ($rebaseline_wp as $r){
                $findIndex[] = array_search($r,$workplan_wp,true);
            }

            $index_rebaseline = 0;
            foreach ($findIndex as $index){

                $workplan[$index]['status'] =  $rebaseline[$index_rebaseline]['ACTION'];
                $workplan[$index]['index_rebaseline'] =  $index_rebaseline;

                $index_rebaseline ++;
            }

            foreach ($workplan as &$wp){
                if($wp['WORK_PERCENT_COMPLETE'] == null){
                    $wp['WORK_PERCENT_COMPLETE'] = 0;
                }
                if($wp['WORK'] == null){
                    $wp['WORK'] = 0;
                }
            }

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


            //for null data tolerance
            if($data == null){
                $data = [];
            }
            $result = array_filter($data, function($elem) { return is_null($elem['WBS_PARENT_ID']); });
            $result['workplan'] = $result[$id_project.'.0'];
            $result['rebaseline_task'] = $rebaseline;
            unset($result[$id_project.'.0']);
            $result['project_status'] = $this->db->query("select project_status from projects where project_id = '$id_project'")->row()->PROJECT_STATUS;
            echo json_encode($result);
        }
        else{
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$id_project'")->row()->RH_ID;
            $workplan=$this->M_detail_project->selectWBS($id_project,$rh_id);
            $workplan_wp = [];
            $rebaseline = $this->M_detail_project->getRebaselineTask($rh_id);
            $rebaseline_wp = [];
            $findIndex = [];

            //store task id in workplan
            foreach($workplan as &$wp){
                $workplan_wp[] = $wp['WBS_ID'];
                $wp['status']='none';
                $wp['index_rebaseline'] = 'none';
            }

            //store task id inside rebaseline
            foreach ($rebaseline as $rwp){
                $rebaseline_wp[] = $rwp['WBS_ID'];
            }

            //find index task that need rebaseline
            foreach ($rebaseline_wp as $r){
                $findIndex[] = array_search($r,$workplan_wp);
            }

            $index_rebaseline = 0;
            foreach ($findIndex as $index){

                $workplan[$index]['status'] =  $rebaseline[$index_rebaseline]['ACTION'];
                $workplan[$index]['index_rebaseline'] =  $index_rebaseline;

                $index_rebaseline ++;
            }

            foreach ($workplan as &$wp){
                if($wp['WORK_PERCENT_COMPLETE'] == null){
                    $wp['WORK_PERCENT_COMPLETE'] = 0;
                }
                if($wp['WORK'] == null){
                    $wp['WORK'] = 0;
                }
            }

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


            //for null data tolerance
            if($data == null){
                $data = [];
            }
            $result = array_filter($data, function($elem) { return is_null($elem['WBS_PARENT_ID']); });
            $result['workplan'] = $result[$id_project.'.0'];
            $result['rebaseline_task'] = $rebaseline;
            unset($result[$id_project.'.0']);
            $result['project_status'] = $this->db->query("select project_status from projects where project_id = '$id_project'")->row()->PROJECT_STATUS;
            echo json_encode($result);
        }

        //echo var_dump($workplan);
    }
    function workplan_view_mobile(){
        $id_project = $this->uri->segment(3);
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$id_project'")->row()->RH_ID;
        $workplan=$this->M_detail_project->selectWBS_mobile($id_project,$rh_id);
        $workplan_wp = [];
        $rebaseline = $this->M_detail_project->getRebaselineTask($rh_id);
        $rebaseline_wp = [];
        $findIndex = [];


        //store task id in workplan
        foreach($workplan as &$wp){
            $workplan_wp[] = $wp['WBS_ID'];
            $wp['status']='none';
            $wp['index_rebaseline'] = 'none';
        }

        //store task id inside rebaseline
        foreach ($rebaseline as $rwp){
            $rebaseline_wp[] = $rwp['WBS_ID'];
        }

        //find index task that need rebaseline
        foreach ($rebaseline_wp as $r){
            $findIndex[] = array_search($r,$workplan_wp);
        }

        $index_rebaseline = 0;
        foreach ($findIndex as $index){

            $workplan[$index]['status'] =  $rebaseline[$index_rebaseline]['ACTION'];
            $workplan[$index]['index_rebaseline'] =  $index_rebaseline;

            $index_rebaseline ++;
        }

        foreach ($workplan as &$wp){
            if($wp['WORK_PERCENT_COMPLETE'] == null){
                $wp['WORK_PERCENT_COMPLETE'] = 0;
            }
            if($wp['WORK'] == null){
                $wp['WORK'] = 0;
            }
        }

        //for null data tolerance
        if($workplan == null){
            $workplan = [];
        }


        $rebaseline = $this->M_detail_project->getRebaselineTask($rh_id);

        $result['workplan'] = $workplan;
        $this->transformKeys($result);
        $result['rebaseline_task'] = $rebaseline;
        echo json_encode($result);


        //echo var_dump($workplan);
    }



    //Create Task
    function createTask_view($project_id){
        $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
        $data['parent']=$this->db->query("select wbs_id,wbs_name,rebaseline
                                        from (select wbs_id,wbs_name,project_id,wbs_parent_id,'no' as rebaseline from wbs
                                              union
                                             select wbs_id,wbs_name,project_id,wbs_parent_id,'yes' as rebaseline from temporary_wbs where rh_id = '$rh_id')
                                        where PROJECT_ID='".$project_id."' connect by  wbs_parent_id= prior wbs_id start with wbs_id='".$project_id.".0' order siblings by wbs_parent_id")->result_array();
        echo json_encode($data);
    }
    function createTask(){
        $project_id   = $this->input->post("PROJECT_ID");

        $statusProject = $this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS;
        $statusProject =strtolower($statusProject);
        if($statusProject == 'on hold'){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
            //wbs id same with project id
            $data['WBS_NAME'] = $this->input->post("WBS_NAME");
            $data['WBS_ID'] = $project_id;
            $data['WBS_PARENT_ID'] = $this->input->post("WBS_PARENT_ID");
            $data['START_DATE']   = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
            $data['FINISH_DATE']  ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";


            // insert into wbs and get new ID
            $newid = $this->M_detail_project->insertWBSTemp($data,$project_id,$rh_id);
            $status['status'] = 'success';
            $status['message'] = 'Task berhasil di tambah temporary';
        }
        elseif($statusProject == 'not started'){
            //wbs id same with project id
            $data['WBS_NAME'] = $this->input->post("WBS_NAME");
            $data['WBS_ID'] = $project_id;
            $data['WBS_PARENT_ID'] = $this->input->post("WBS_PARENT_ID");
            $data['START_DATE']   = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
            $data['FINISH_DATE']  ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";

            // insert into wbs and get new ID
            $newid = $this->M_detail_project->insertWBS($data,$project_id);
            $selWBS=$this->getSelectedWBS($newid);
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
                $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
                $wc=0;
                $allChild=$this->getAllChildWBS($ap->WBS_ID);
                foreach ($allChild as $ac) {
                    //child total hour
                    $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
                    $duration = $this->db->query("select duration from wbs where wbs_id = '$ac->WBS_ID'")->row()->DURATION;
                    $wc=$wc+$works;
                }
                $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc' where wbs_id='$ap->WBS_ID'");
                //$this->M_detail_project->updateNewDuration($ap->WBS_ID);
            }

            $status['status'] = 'success';
            $status['message'] = 'Task berhasil di tambah';
        }
        elseif($statusProject == 'in progress' && $this->wp_modif){
            $data['WBS_NAME'] = $this->input->post("WBS_NAME");
            $data['WBS_ID'] = $project_id;
            $data['WBS_PARENT_ID'] = $this->input->post("WBS_PARENT_ID");
            $data['START_DATE']   = "TO_DATE('".$this->input->post('START_DATE')."','yyyy-mm-dd')";
            $data['FINISH_DATE']  ="TO_DATE('".$this->input->post("FINISH_DATE")."','yyyy-mm-dd')";

            $newid = $this->M_detail_project->insertWBSEditTemp($data,$project_id);
            $status['status'] = 'success';
            $status['message'] = 'Task berhasil di tambah temporary';
        }
        else{
            $this->output->set_status_header(400);
            $status['status'] = 'failed';
            $status['message'] = 'Task gagal di tambah';
        }
        echo json_encode($status);
    }

    //EDIT TASK
    //duplicate parent if parent edited
    function editTask_view($wbs_id)
    {
        $project_id = explode(".",$wbs_id);
        $rh_id = $this->db->query("select rh_id from projects where project_id = '".$project_id[0]."'")->row()->RH_ID;
        $status_project= $this->db->query("select lower(project_status) as project_status from projects where project_id = '".$project_id[0]."'")->row()->PROJECT_STATUS;
        if($status_project == 'in progress' && $this->wp_modif){
            //check wbs table
            $checktemp = $this->db->query("select count(*) as hasil from temporary_edit_wbs where wbs_id = '$wbs_id'")->row()->HASIL;
            if($checktemp){
                $query = $this->db->query("select * from temporary_edit_wbs where WBS_ID='".$wbs_id."'");
            }
            else{
                $query = $this->db->query("select * from wbs where WBS_ID='".$wbs_id."'");
            }
            $data['detail_task'] = $query->result_array();
            $data['parent']=$this->db->query("select wbs_id,wbs_name,rebaseline from 
                                                                          (select wbs_id,wbs_name,project_id,wbs_parent_id,'no' as rebaseline from wbs where wbs_id not in(select wbs_id from temporary_edit_wbs where project_id = '".$project_id[0]."')
                                                                          union 
                                                                          select wbs_id,wbs_name,project_id,wbs_parent_id,'yes' as rebaseline from temporary_edit_wbs) 
                                              where PROJECT_ID='".$project_id[0]."' 
                                              connect by  wbs_parent_id= prior wbs_id start with wbs_id='".$project_id[0].".0' 
                                              order siblings by wbs_parent_id")->result_array();
        }
        else{
            $query = $this->db->query("select * from wbs where WBS_ID='".$wbs_id."'");
            $data['detail_task'] = $query->result_array();
            $data['parent']=$this->db->query("select wbs_id,wbs_name,rebaseline from 
                                                                          (select wbs_id,wbs_name,project_id,wbs_parent_id,'no' as rebaseline from wbs where wbs_id not in(select wbs_id from temporary_wbs where project_id = '".$project_id[0]."' and rh_id  = '$rh_id')
                                                                          union 
                                                                          select wbs_id,wbs_name,project_id,wbs_parent_id,'yes' as rebaseline from temporary_wbs where rh_id  = '$rh_id') 
                                              where PROJECT_ID='".$project_id[0]."' 
                                              connect by  wbs_parent_id= prior wbs_id start with wbs_id='".$project_id[0].".0' 
                                              order siblings by wbs_parent_id")->result_array();
        }

        echo json_encode($data);
    }

    function editTask_action(){


        $project_id   = $this->input->post("project_id");

        $statusProject = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);
        if($statusProject == 'on hold'){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
            $wbs=$this->input->post("WBS_ID");
            $this->M_detail_project->Edit_WBSTemp(
                $_POST["wbs_id"],
                $_POST["wbs_parent_id"],
                $_POST["project_id"],
                $_POST["wbs_name"],
                $_POST['start_date'],
                $_POST['finish_date'],
                $rh_id
                );
            $status['status']= 'success';
            $status['message'] = 'Task berhasil di edit temporary';

        }
        elseif($statusProject == 'not started'){
            $wbs=$this->input->post("WBS_ID");
            $this->M_detail_project->Edit_WBS(
                $_POST["wbs_id"],
                $_POST["wbs_parent_id"],
                $_POST["project_id"],
                $_POST["wbs_name"],
                $_POST['start_date'],
                $_POST['finish_date']
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
            $status['status']= 'success';
            $status['message'] = 'Task berhasil di edit';
        }
        //if in progress
        elseif($statusProject == 'in progress' && $this->wp_modif){

            $WBS_ID = $this->input->post('wbs_id');
            $WBS_PARENT_ID = $this->input->post('wbs_parent_id');
            $PROJECT_ID = $this->input->post('project_id');
            $WBS_NAME = $this->input->post('wbs_name');
            $START_DATE = $this->input->post('start_date');
            $FINISH_DATE = $this->input->post('finish_date');

            $check_wbs = $this->db->query("select count(*) as hasil from TEMPORARY_EDIT_WBS where wbs_id = '$WBS_ID'")->row()->HASIL;

            //if temporary_edit_wbs have a data that associate with this wbs id
            if($check_wbs){
              $sql =   "UPDATE TEMPORARY_EDIT_WBS SET
                  WBS_PARENT_ID='".$WBS_PARENT_ID."',
                  PROJECT_ID='".$PROJECT_ID."',
                  WBS_NAME='".$WBS_NAME."',
                  "."START_DATE=to_date('".$START_DATE."','yyyy-mm-dd'),
                  FINISH_DATE=to_date('".$FINISH_DATE."','yyyy-mm-dd')
                  WHERE WBS_ID='".$WBS_ID."'
                  ";
                $dur=$this->db->query("select COUNT_DURATION from (SELECT   COUNT (TRUNC (a.start_date + delta)) count_duration, wbs_id
       FROM TEMPORARY_EDIT_WBS a,
            (SELECT     LEVEL - 1 AS delta
                   FROM DUAL
             CONNECT BY LEVEL - 1 <= (SELECT MAX (finish_date - start_date)
                                        FROM wbs))
      WHERE TRUNC (a.start_date + delta) <= TRUNC (a.finish_date)
        AND TO_CHAR (TRUNC (start_date + delta),
                     'DY',
                     'NLS_DATE_LANGUAGE=AMERICAN'
                    ) NOT IN ('SAT', 'SUN')
        AND TRUNC (a.start_date + delta) NOT IN (SELECT dt
                                                   FROM v_holiday_excl_weekend)
   GROUP BY wbs_id
   ORDER BY wbs_id) where wbs_id='$WBS_ID'")->row()->COUNT_DURATION;
                ($dur == 0 || $dur == null ?$dur = 1 : $dur = $dur );
                $hour_total = $dur * 8 ;
                $this->db->query("update temporary_edit_wbs set duration='$dur',work_complete = '$hour_total' where wbs_id='$WBS_ID'");
                $this->db->query($sql);
            }
            else{
                $sql = "insert into temporary_edit_wbs (wbs_id,wbs_parent_id,project_id,wbs_name,start_date,finish_date,action) VALUES 
                        ('$WBS_ID','$WBS_PARENT_ID','$PROJECT_ID','$WBS_NAME',to_date('$START_DATE','YYYY-MM-DD'),to_date('$FINISH_DATE','YYYY-MM-DD'),'update')";
                $this->db->query($sql);
                $dur=$this->db->query("select COUNT_DURATION from (SELECT   COUNT (TRUNC (a.start_date + delta)) count_duration, wbs_id
       FROM TEMPORARY_EDIT_WBS a,
            (SELECT     LEVEL - 1 AS delta
                   FROM DUAL
             CONNECT BY LEVEL - 1 <= (SELECT MAX (finish_date - start_date)
                                        FROM wbs))
      WHERE TRUNC (a.start_date + delta) <= TRUNC (a.finish_date)
        AND TO_CHAR (TRUNC (start_date + delta),
                     'DY',
                     'NLS_DATE_LANGUAGE=AMERICAN'
                    ) NOT IN ('SAT', 'SUN')
        AND TRUNC (a.start_date + delta) NOT IN (SELECT dt
                                                   FROM v_holiday_excl_weekend)
   GROUP BY wbs_id
   ORDER BY wbs_id) where wbs_id='$WBS_ID'")->row()->COUNT_DURATION;
                ($dur == 0 || $dur == null ?$dur = 1 : $dur = $dur );
                $hour_total = $dur * 8 ;
                $this->db->query("update temporary_edit_wbs set duration='$dur',work_complete = '$hour_total' where wbs_id='$WBS_ID'");

            }



            /*EDIT WBS NAME ONLY
            $name = $this->input->post('wbs_name');
            $wbs_id = $this->input->post('wbs_id');
            $this->db->query("update wbs set wbs_name = '$name' where wbs_id = '$wbs_id'");
            if($this->db->affected_rows() == 1){
                $status['status']= 'Succes';
                $status['message'] = 'Task Name Updated';
            }
            else{
                $this->output->set_status_header(500);
                $status['status']= 'failed';
                $status['message'] = 'Task name not updated';
            }*/

        }
        $status['status']= 'Succes';
        $status['message'] = 'Task Updated';
        echo json_encode($status);

    }

    //delete task
    public function deleteTask()
    {

        $id = $_POST['wbs_id'];
        $wbs_id = $_POST['wbs_id'];
        $project_id = (explode(".",$id))[0];
        $statusProject = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);
        if($statusProject == 'on hold'){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
            $this->M_detail_project->updateProgressDeleteTaskTemp($wbs_id,$rh_id);
            $returndata['status'] = "success";
            $returndata['message'] = "Task temporary deleted success";
        }
        elseif($statusProject == 'not started'){
            //$this->M_detail_project->deleteWBSID($id);
            //$this->M_detail_project->deleteWBSPoolID($id);
            $this->M_detail_project->updateProgressDeleteTask($wbs_id);
            $returndata['status'] = "success";
            $returndata['message'] = "Task delete success";
        }
        //if project status in progress
        elseif($statusProject == 'in progress' && $this->wp_modif){
            $checktemp = $this->db->query("select count(*) as hasil from temporary_edit_wbs where wbs_id = '$wbs_id'")->row()->HASIL;
            if($checktemp){
                $this->db->query("delete from temporary_edit_wbs where wbs_id = '$wbs_id'");
            }
            else{
                $this->db->query("insert into temporary_edit_wbs(wbs_id,project_id,action) values('$wbs_id','$project_id','delete')");
            }
            $returndata['status'] = "success";
            $returndata['message'] = "task temporary deleted success";
        }


        echo json_encode($returndata);
    }

    //Update Task Complete Percent
    public function editTaskPercent(){
        $data['WBS_ID']=$this->input->post("WBS_ID");
        $data['PROJECT_ID']=$this->input->post("PROJECT_ID");
        $data['WORK_PERCENT_COMPLETE']=$this->input->post("WORK_PERCENT_COMPLETE");

        if($this->input->post("WORK_PERCENT_COMPLETE") == 0 || $this->input->post("WORK_PERCENT_COMPLETE") == null){
            $this->output->set_status_header(400);
            $returndata['status'] = "failed";
            $returndata['message'] = 'percent must more than 0%';
            die;
        }
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
        $status_project = $this->db->query("select lower(project_status) as project_status from projects where project_id = '$project'")->row()->PROJECT_STATUS;

        if($status_project == 'in progress' && $this->wp_modif){
            $wbs_id=$this->input->post('WBS_ID');
            $data['task_name'] = $this->db->query("SELECT * FROM (select wbs_id, wbs_name from wbs union select wbs_id, wbs_name from temporary_edit_wbs ) WHERE WBS_ID='".$wbs_id."'")->row()->WBS_NAME;
            $data['available_to_assign'] =  $this->db->query("
                                                            SELECT RESOURCE_POOL.RP_ID, users.user_name,users.email,'no' as rebaseline FROM RESOURCE_POOL
                                                            join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
                                                            join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
                                                            WHERE PROJECT_ID='$project' and RESOURCE_POOL.user_id not in(
                                                              select user_id
                                                              from wbs_pool
                                                              inner join resource_pool
                                                              on wbs_pool.rp_id=resource_pool.rp_id
                                                              where wbs_id='$wbs_id'
                                                              UNION
                                                              select user_id
                                                              from temporary_edit_wbs_pool
                                                              inner join resource_pool
                                                              on temporary_edit_wbs_pool.rp_id=resource_pool.rp_id
                                                              where wbs_id='$wbs_id')
                                                            group by RESOURCE_POOL.RP_ID, users.user_name,users.email
                                                            ")->result_array();

            $data['currently_assigned']=$this->db->query("SELECT RESOURCE_POOL.RP_ID, users.user_name,users.email,'no' as rebaseline FROM RESOURCE_POOL
                                                          join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
                                                          join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
                                                          WHERE PROJECT_ID='$project' and RESOURCE_POOL.user_id  in
                                                          (select user_id from (select rp_id,wbs_id from wbs_pool union select rp_id,wbs_id from temporary_edit_wbs_pool) wbs_pool inner join resource_pool on wbs_pool.rp_id=resource_pool.rp_id where wbs_id='$wbs_id')
                                                          and rp_id not in 
                                                          (
                                                              SELECT RESOURCE_POOL.RP_ID FROM RESOURCE_POOL
                                                              join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
                                                              join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
                                                              WHERE PROJECT_ID='$project' and RESOURCE_POOL.user_id  in
                                                              (select user_id from temporary_edit_wbs_pool inner join resource_pool on temporary_edit_wbs_pool.rp_id=resource_pool.rp_id where wbs_id='$wbs_id')
                                                              group by RESOURCE_POOL.RP_ID, users.user_name,users.email
                                                          )
                                                          group by RESOURCE_POOL.RP_ID, users.user_name,users.email
                                                          UNION
                                                          SELECT RESOURCE_POOL.RP_ID, users.user_name,users.email,'yes' as rebaseline FROM RESOURCE_POOL
                                                          join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
                                                          join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
                                                          WHERE PROJECT_ID='$project' and RESOURCE_POOL.user_id  in
                                                          (select user_id from temporary_edit_wbs_pool inner join resource_pool on temporary_edit_wbs_pool.rp_id=resource_pool.rp_id where wbs_id='$wbs_id')
                                                          group by RESOURCE_POOL.RP_ID, users.user_name,users.email")->result_array();
            if(count($data['currently_assigned'])){
                foreach ($data['currently_assigned'] as &$curass){
                    $curass['status']= 'none';
                }
            }


            if(count($data['available_to_assign'])){
                foreach ($data['available_to_assign'] as &$curass){
                    $curass['status']= 'none';
                }
            }

            $data['rebaseline'] = $this->db->query("
            SELECT RESOURCE_POOL.RP_ID, users.user_name,users.email,'yes' as rebaseline,action FROM RESOURCE_POOL
            join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
            join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
            join TEMPORARY_EDIT_WBS_POOL on TEMPORARY_EDIT_WBS_POOL.RP_ID = RESOURCE_POOL.RP_ID
            WHERE PROJECT_ID='$project' and TEMPORARY_EDIT_WBS_POOL.WBS_ID = '$wbs_id'
            group by RESOURCE_POOL.RP_ID, users.user_name,users.email,action")->result_array();

            $curass_id =[];
            foreach ($data['currently_assigned'] as $dd){
                $curass_id[] = $dd['RP_ID'];
            }

            $reb_id = [];
            foreach ($data['rebaseline'] as $dd){
                $reb_id[] = $dd['RP_ID'];
            }

            $find_index = [];
            foreach ($reb_id as $ddd){
                $find_index[] = array_search($ddd,$curass_id);
            }

            $index_rebaseline = 0;
            foreach ($find_index as $add){
                $data['currently_assigned'][$add]['status']= $data['rebaseline'][$index_rebaseline]['ACTION'];
                $index_rebaseline++;
            }

            echo json_encode($data);
        }
        else{
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project'")->row()->RH_ID;
            $wbs_id=$this->input->post('WBS_ID');
            $data['task_name'] = $data['task_name'] = $this->db->query("SELECT * FROM (select wbs_id, wbs_name from wbs union select wbs_id, wbs_name from temporary_wbs where rh_id = '$rh_id') WHERE WBS_ID='".$wbs_id."'")->row()->WBS_NAME;
            $data['available_to_assign'] = $this->M_detail_project->getWBSAvailableUser($project,$wbs_id,$rh_id);

            $data['currently_assigned']=$this->M_detail_project->getWBSselectedUser($project,$wbs_id,$rh_id);
            if(count($data['currently_assigned'])){
                foreach ($data['currently_assigned'] as &$curass){
                    $curass['status']= 'none';
                }
            }


            if(count($data['available_to_assign'])){
                foreach ($data['available_to_assign'] as &$curass){
                    $curass['status']= 'none';
                }
            }

            $data['rebaseline'] = $this->db->query("
            SELECT RESOURCE_POOL.RP_ID, users.user_name,users.email,'yes' as rebaseline,action FROM RESOURCE_POOL
            join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
            join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
            join TEMPORARY_WBS_POOL on TEMPORARY_WBS_POOL.RP_ID = RESOURCE_POOL.RP_ID
            WHERE PROJECT_ID='$project' and TEMPORARY_WBS_POOL.RH_ID = '$rh_id'
            group by RESOURCE_POOL.RP_ID, users.user_name,users.email,action")->result_array();

            $curass_id =[];
            foreach ($data['currently_assigned'] as $dd){
                $curass_id[] = $dd['RP_ID'];
            }

            $reb_id = [];
            foreach ($data['rebaseline'] as $dd){
                $reb_id[] = $dd['RP_ID'];
            }

            $find_index = [];
            foreach ($reb_id as $ddd){
                $find_index[] = array_search($ddd,$curass_id);
            }

            $index_rebaseline = 0;
            foreach ($find_index as $add){
                $data['currently_assigned'][$add]['status']= $data['rebaseline'][$index_rebaseline]['ACTION'];
                $index_rebaseline++;
            }

            echo json_encode($data);
        }
    }

    //Remove task from task member
    public function removeTaskMemberProject(){

        $project_id = explode(".",$_POST['WBS_ID']);
        $project_id = $project_id[0];
        $statusProject = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);
        if($statusProject == 'on hold'){
            $this->M_detail_project->removeAssignementTemp();
            $data['status'] = 'success';
            $data['message'] = 'Task member berhasil di hapus temporary';
        }
        elseif($statusProject == 'not started'){
            $this->M_detail_project->removeAssignement();

            //send email
            $email=$this->input->post('EMAIL');
            $user_name=$this->input->post('NAME');
            $wbs_name=$this->input->post('WBS_NAME');
            $this->sendVerificationremoveMember($email,$user_name,$wbs_name);
            $data['status'] = 'success';
            $data['message'] = 'Task member berhasil di hapus';
        }
        elseif($statusProject == 'in progress' && $this->wp_modif){
            $wbs=$this->input->post('WBS_ID');
            $member=$this->input->post('RP_ID');
            $project_id = explode(".",$_POST['WBS_ID']);
            $project_id = $project_id[0];

            $wp_id = $this->db->query("select NVL(max(cast(WP_ID as int))+1, 1) as NEW_ID from (select wp_id,wbs_id from wbs_pool union select wp_id,wbs_id from temporary_edit_wbs_pool) where wbs_id = '$wbs'")->row()->NEW_ID;

            //Assign primary key of wbs pool id to temporary with status delete ,so in the future
            //if rebaseline acc ,calucation will happen
            $this->db->query("insert into temporary_edit_wbs_pool (WP_ID,RP_ID,WBS_ID,ACTION ) values('$wp_id','$member','$wbs','delete')");
            $data['status'] = 'success';
            $data['message'] = 'Task member berhasil di hapus temporary';
        }
        else{
            $this->output->set_status_header(400);
            $data['status'] = 'failed';
            $data['message'] = 'gagal remove task member';
        }
        echo json_encode($data);
    }

    //Assign task to project member
    public function assignTaskMemberProject(){
        $project_id = explode(".",$_POST['WBS_ID']);
        $project_id = $project_id[0];
        $statusProject = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);
        if($statusProject == 'on hold'){
            $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;

            $wbs=$this->input->post('WBS_ID');
            $member=$this->input->post('MEMBER');
            $project_id = $this->M_detail_project->getRPProject($member);
            $sql = "select * from TEMPORARY_WBS_POOL wbs join wbs w on w.wbs_id=wbs.wbs_id join RESOURCE_POOL rp on rp.rp_id=wbs.rp_id join USERS on rp.user_id=users.user_id where wbs.WBS_ID=$wbs";
            $q = $this->db->query($sql);
            if($q->num_rows() > 0){
                $user = $q->row_array();
            }
            $this->sendVerificationassignMember($user["EMAIL"],$user["USER_NAME"],$user["WBS_NAME"],$project_id);

            $this->M_detail_project->postAssignmentTemp($rh_id);
            $data['status'] = 'success';
            $data['message'] = 'member di tambah temporary';
        }
        elseif($statusProject == 'not started'){
            //assign process
            $this->M_detail_project->postAssignment();
            //send email
            $wbs=$this->input->post('WBS_ID');
            $member=$this->input->post('MEMBER');
            $project_id = $this->M_detail_project->getRPProject($member);
            $sql = "select * from WBS_POOL wbs join wbs w on w.wbs_id=wbs.wbs_id join RESOURCE_POOL rp on rp.rp_id=wbs.rp_id join USERS on rp.user_id=users.user_id where wbs.WBS_ID=$wbs";
            $q = $this->db->query($sql);
            if($q->num_rows() > 0){
                $user = $q->row_array();
            }
            $this->sendVerificationassignMember($user["EMAIL"],$user["USER_NAME"],$user["WBS_NAME"],$project_id);
            $data['status'] = 'success';
            $data['message'] = 'member di tambah';
        }
        elseif($statusProject == 'in progress' && $this->wp_modif){
            $wbs=$this->input->post('WBS_ID');

            $checktemp = $this->db->query("select count(*) as hasil from temporary_edit_wbs WHERE wbs_id = '$wbs'")->row()->HASIL;

            $member=$this->input->post('MEMBER');

            if($checktemp){
                $sql = "select * from TEMPORARY_EDIT_WBS_POOL wbs join wbs w on w.wbs_id=wbs.wbs_id join RESOURCE_POOL rp on rp.rp_id=wbs.rp_id join USERS on rp.user_id=users.user_id where wbs.WBS_ID=$wbs";
            }
            else{
                $sql = "select * from WBS_POOL wbs join wbs w on w.wbs_id=wbs.wbs_id join RESOURCE_POOL rp on rp.rp_id=wbs.rp_id join USERS on rp.user_id=users.user_id where wbs.WBS_ID=$wbs";
            }
            $q = $this->db->query($sql);
            if($q->num_rows() > 0){
                $user = $q->row_array();
            }
            //$this->sendVerificationassignMember($user["EMAIL"],$user["USER_NAME"],$user["WBS_NAME"],$project_id);

            $wbs=$this->input->post('WBS_ID');
            $member=$this->input->post('MEMBER');

            $id = $this->db->query("select NVL(max(cast(WP_ID as int))+1, 1) as NEW_ID from (
                                select WP_ID from WBS_POOL
                                UNION
                                select WP_ID from TEMPORARY_EDIT_WBS_POOL)")->row()->NEW_ID;
            $this->db->set('RP_ID', $member);
            $this->db->set('WP_ID', $id);
            $this->db->set('WBS_ID', $wbs);
            $this->db->set('ACTION', 'create');
            $this->db->insert("TEMPORARY_EDIT_WBS_POOL");

            $data['status'] = 'success';
            $data['message'] = 'member di tambah temporary';
            }
        //return
        echo json_encode($data);

    }


    public function upload_wbs() {

        $project_id = $this->input->post('project_id');

        $project_status = strtolower($this->db->query("select project_status from projects where project_id = '$project_id'")->row()->PROJECT_STATUS);

        if($project_status == 'not started'){


            $config['upload_path'] = 'document_assets/temp_upload';
            $config['allowed_types'] = 'xlsx|xls|csv';

            $this->load->library('upload', $config);

            if ( ! $this->upload->do_upload('document'))
            {
                $data = array('error' => $this->upload->display_errors());
                //    $data['nav']=($this->load->view('v_nav1'));
                //  $data['header']=($this->load->view('v_header'));

                $datajson['status'] = "failed";
                $datajson['message']= "gagal upload file";
                //echo "<script>alert('berhasil')</script>";
                //$this->load->view('uploadwbs', $data);

                //$this->load->view('uploadwbs', $data);
                //redirect('/Importwbs');
                echo json_encode($data);
            }
            else
            {
                $this->load->model('M_wbs');
                $data = array('error' => false);
                $upload_data = $this->upload->data();
                $this->load->library('excel_reader');
                $this->excel_reader->setOutputEncoding('230787');
                $file =  $upload_data['full_path'];
                $this->excel_reader->read($file);
                error_reporting(E_ALL ^ E_NOTICE);
                // Sheet 1
                $data = $this->excel_reader->sheets[0] ;
                $coba = $data['numRows'];
                $dataexcel = Array();
                for ($i = 1; $i <= $data['numRows']; $i++) {
                    if($data['cells'][$i][1] == '')
                        break;

                    $cars = $data['cells'][$i][1];
                    $array[]=$data['cells'][$i][1];
                    $dataexcel[$i-1]['anjay']=$array;
                    $dataexcel[$i-1]['PROJECT_ID']=$this->input->post('project_id');
                    //}
                    //$dataexcel[$i-1]['WBS_ID']= $data['cells'][$i][1];
                    //  $dataexcel[$i-1]['WBS_PARENT_ID']= $data['cells'][$i][2];
                    //$dataexcel[$i-1]['IWO_NO']= $data['cells'][$i][3];
                    //$dataexcel[$i-1]['USER_ID']= $data['cells'][$i][2];
                    $dataexcel[$i-1]['WBS_NAME']= $data['cells'][$i][4];
                    //$dataexcel[$i-1]['WBS_DESC']= $data['cells'][$i][];
                    //$dataexcel[$i-1]['PRIORITY']= $data['cells'][$i][5];
                    //  $dataexcel[$i-1]['CALCULATION_TYPE']= $data['cells'][$i][6];
                    //    $dataexcel[$i-1]['USER_TAG']= $data['cells'][$i][7];
                    //  $dataexcel[$i-1]['PHASE']= $data['cells'][$i][8];
                    //$dataexcel[$i-1]['EFFORT_DRIVEN']= $data['cells'][$i][9];
                    //$dataexcel[$i-1]['WORK']=trim($data['cells'][$i][5]," hrs");
                    @$dur =$this->countDuration(date_format(date_create($data['cells'][$i][6]),"Y/m/d"), date_format(date_create($data['cells'][$i][7]),"Y/m/d"));
                    //$dataexcel[$i-1]['DURATION']= floor(trim($data['cells'][$i][5]," days"));
                    $dataexcel[$i-1]['DURATION']= $dur;
                    //$dataexcel[$i-1]['START_DATE']= date('d/m/Y',strtotime($data['cells'][$i][6]));
                    @$dataexcel[$i-1]['START_DATE']= date_format(date_create($data['cells'][$i][6]),"d/m/Y");
                    $dataexcel[$i-1]['START_DATEs']= $data['cells'][$i][6];
                    //$dataexcel[$i-1]['ACTUAL_START_DATE']= $data['cells'][$i][11];
                    @$dataexcel[$i-1]['FINISH_DATE']= date_format(date_create($data['cells'][$i][7]),"d/m/Y");
                    //$dataexcel[$i-1]['FINISH_DATE']= date('d/m/Y',strtotime($data['cells'][$i][7]));
                    $dataexcel[$i-1]['FINISH_DATEs']= $data['cells'][$i][7];
                    //$dataexcel[$i-1]['ACTUAL_FINISH_DATE']= $data['cells'][$i][13];


                    //$dataexcel[$i-1]['MILESTONE']= $data['cells'][$i][16];
                    //$dataexcel[$i-1]['WORK_COMPLETE']= $data['cells'][$i][7];
                    //    $dataexcel[$i-1]['WORK_PERCENT_COMPLETE']= $data['cells'][$i][18];
                    //  $dataexcel[$i-1]['CONSTRAINT_TYPE']= $data['cells'][$i][19];
                    //  $dataexcel[$i-1]['CONSTRAINT_DATE']= $data['cells'][$i][20];
                    //  $dataexcel[$i-1]['DEADLINE']= $data['cells'][$i][21];
                    //    $dataexcel[$i-1]['WBS_PARENT_ID']= $data['cells'][$i][22];
                    //    $dataexcel[$i-1]['ACHIEVEMENT']= $data['cells'][$i][23];
                    //  $dataexcel[$i-1]['ID']= $data['cells'][$i][24];
                    //$dataexcel[$i-1]['TEXT']= $data['cells'][$i][25];
                    //$dataexcel[$i-1]['PROGRESS']= $data['cells'][$i][26];
                    //$dataexcel[$i-1]['SORTORDER']= $data['cells'][$i][27];
                    //$dataexcel[$i-1]['PARENT']= $data['cells'][$i][28];
                    //  $dataexcel[$i-1]['PLANNED_START']= $data['cells'][$i][29];
                    //$dataexcel[$i-1]['PLANNED_END']= $data['cells'][$i][30];
                    //$dataexcel[$i-1]['END_DATE']= $data['cells'][$i][31];
                }

                //echo json_encode($dataexcel);
                $this->M_wbs->tambahwbs($dataexcel);
                //$data['ini']=$dataexcel;
                //$data['nav']=($this->load->view('v_nav1'));
                //  $data['header']=($this->load->view('v_header'));
                delete_files($file);
                $datajson['status'] = 'success';
                $datajson['message'] = 'Data berhasil di upload';
                echo json_encode($datajson);
                //$this->session->set_flashdata("bukanpesan",json_encode($dataexcel));
                //$this->session->set_flashdata("bukanpesan",$this->M_wbs->tambahwbs($dataexcel));
                //echo "<script>alert('berhasil')</script>";
                //$this->load->view('uploadwbs', $data);


            }
        }
        elseif ($project_status == 'on hold'){


            $config['upload_path'] = 'document_assets/temp_upload';
            $config['allowed_types'] = 'xlsx|xls|csv';

            $this->load->library('upload', $config);

            if ( ! $this->upload->do_upload('document'))
            {
                $data = array('error' => $this->upload->display_errors());
                //    $data['nav']=($this->load->view('v_nav1'));
                //  $data['header']=($this->load->view('v_header'));

                $datajson['status'] = "failed";
                $datajson['message']= "gagal upload file";
                //echo "<script>alert('berhasil')</script>";
                //$this->load->view('uploadwbs', $data);

                //$this->load->view('uploadwbs', $data);
                //redirect('/Importwbs');
                echo json_encode($data);
            }
            else
            {
                $this->load->model('M_wbs');
                $rh_id = $this->db->query("select rh_id from projects where project_id = '$project_id'")->row()->RH_ID;
                $data = array('error' => false);
                $upload_data = $this->upload->data();
                $this->load->library('excel_reader');
                $this->excel_reader->setOutputEncoding('230787');
                $file =  $upload_data['full_path'];
                $this->excel_reader->read($file);
                error_reporting(E_ALL ^ E_NOTICE);
                // Sheet 1
                $data = $this->excel_reader->sheets[0] ;
                $coba = $data['numRows'];
                $dataexcel = Array();
                for ($i = 1; $i <= $data['numRows']; $i++) {
                    if($data['cells'][$i][1] == '')
                        break;

                    $cars = $data['cells'][$i][1];
                    $array[]=$data['cells'][$i][1];
                    $dataexcel[$i-1]['anjay']=$array;
                    $dataexcel[$i-1]['PROJECT_ID']=$this->input->post('project_id');
                    //}
                    //$dataexcel[$i-1]['WBS_ID']= $data['cells'][$i][1];
                    //  $dataexcel[$i-1]['WBS_PARENT_ID']= $data['cells'][$i][2];
                    //$dataexcel[$i-1]['IWO_NO']= $data['cells'][$i][3];
                    //$dataexcel[$i-1]['USER_ID']= $data['cells'][$i][2];
                    $dataexcel[$i-1]['WBS_NAME']= $data['cells'][$i][4];
                    //$dataexcel[$i-1]['WBS_DESC']= $data['cells'][$i][];
                    //$dataexcel[$i-1]['PRIORITY']= $data['cells'][$i][5];
                    //  $dataexcel[$i-1]['CALCULATION_TYPE']= $data['cells'][$i][6];
                    //    $dataexcel[$i-1]['USER_TAG']= $data['cells'][$i][7];
                    //  $dataexcel[$i-1]['PHASE']= $data['cells'][$i][8];
                    //$dataexcel[$i-1]['EFFORT_DRIVEN']= $data['cells'][$i][9];
                    //$dataexcel[$i-1]['WORK']=trim($data['cells'][$i][5]," hrs");
                    @$dur =$this->countDuration(date_format(date_create($data['cells'][$i][6]),"Y/m/d"), date_format(date_create($data['cells'][$i][7]),"Y/m/d"));
                    //$dataexcel[$i-1]['DURATION']= floor(trim($data['cells'][$i][5]," days"));
                    $dataexcel[$i-1]['DURATION']= $dur;
                    //$dataexcel[$i-1]['START_DATE']= date('d/m/Y',strtotime($data['cells'][$i][6]));
                    @$dataexcel[$i-1]['START_DATE']= date_format(date_create($data['cells'][$i][6]),"d/m/Y");
                    $dataexcel[$i-1]['START_DATEs']= $data['cells'][$i][6];
                    //$dataexcel[$i-1]['ACTUAL_START_DATE']= $data['cells'][$i][11];
                    @$dataexcel[$i-1]['FINISH_DATE']= date_format(date_create($data['cells'][$i][7]),"d/m/Y");
                    //$dataexcel[$i-1]['FINISH_DATE']= date('d/m/Y',strtotime($data['cells'][$i][7]));
                    $dataexcel[$i-1]['FINISH_DATEs']= $data['cells'][$i][7];
                    //$dataexcel[$i-1]['ACTUAL_FINISH_DATE']= $data['cells'][$i][13];


                    //$dataexcel[$i-1]['MILESTONE']= $data['cells'][$i][16];
                    //$dataexcel[$i-1]['WORK_COMPLETE']= $data['cells'][$i][7];
                    //    $dataexcel[$i-1]['WORK_PERCENT_COMPLETE']= $data['cells'][$i][18];
                    //  $dataexcel[$i-1]['CONSTRAINT_TYPE']= $data['cells'][$i][19];
                    //  $dataexcel[$i-1]['CONSTRAINT_DATE']= $data['cells'][$i][20];
                    //  $dataexcel[$i-1]['DEADLINE']= $data['cells'][$i][21];
                    //    $dataexcel[$i-1]['WBS_PARENT_ID']= $data['cells'][$i][22];
                    //    $dataexcel[$i-1]['ACHIEVEMENT']= $data['cells'][$i][23];
                    //  $dataexcel[$i-1]['ID']= $data['cells'][$i][24];
                    //$dataexcel[$i-1]['TEXT']= $data['cells'][$i][25];
                    //$dataexcel[$i-1]['PROGRESS']= $data['cells'][$i][26];
                    //$dataexcel[$i-1]['SORTORDER']= $data['cells'][$i][27];
                    //$dataexcel[$i-1]['PARENT']= $data['cells'][$i][28];
                    //  $dataexcel[$i-1]['PLANNED_START']= $data['cells'][$i][29];
                    //$dataexcel[$i-1]['PLANNED_END']= $data['cells'][$i][30];
                    //$dataexcel[$i-1]['END_DATE']= $data['cells'][$i][31];
                }

                //echo json_encode($dataexcel);
                $this->M_wbs->tambahwbsTemp($dataexcel,$rh_id);
                //$data['ini']=$dataexcel;
                //$data['nav']=($this->load->view('v_nav1'));
                //  $data['header']=($this->load->view('v_header'));
                delete_files($file);
                $datajson['status'] = 'success';
                $datajson['message'] = 'Data berhasil di upload';
                echo json_encode($datajson);
                //$this->session->set_flashdata("bukanpesan",json_encode($dataexcel));
                //$this->session->set_flashdata("bukanpesan",$this->M_wbs->tambahwbs($dataexcel));
                //echo "<script>alert('berhasil')</script>";
                //$this->load->view('uploadwbs', $data);


            }
        }
        else{
            $this->output->set_status_header(400);
            $datajson['status'] = 'error';
            $datajson['message'] = 'Status project in progress';
            echo json_encode($datajson);
        }


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
            $this->email->to("emil.gunawan.h@gmail.com");
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

    private function getSelectedWBS($id){
        return $this->M_detail_project->getWBSselected($id);
    }

    private function getAllParent($id){
        return $this->M_detail_project->getAllParentWBS($id);
    }

    function last_day($month = '', $year = '')
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
    function getHolidays() {
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

    function countDuration($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = $end->diff($start);
            //print_r($interval);
        $days = $interval->days;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
            //print_r($period);
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
    function countDurationAll($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = $end->diff($start);
        $days = $interval->days;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        return $days;
    }
    function dateRange($first, $last, $step = '+1 day', $output_format = 'Y-m-d') {

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
    public function getCurrentProgresTask(){
      $c['WBS_ID']=$_POST['wbs_id'];
      $sql="SELECT * FROM WBS WHERE WBS_ID='".$_POST['wbs_id']."' ";
      $data=$this->db->query($sql)->row_array();
      $c['PROJECT_ID']=$data['PROJECT_ID'];
      $c['WORK_PERCENT_COMPLETE']=$data['WORK_PERCENT_COMPLETE'];
      echo json_encode($c, JSON_NUMERIC_CHECK);
    }
    function getAllChildWBS($wbs){
        return $this->db->query("SELECT CONNECT_BY_ISLEAF AS LEAF, WBS.*, LEVEL
              FROM WBS where WBS_ID NOT IN ('$wbs') and CONNECT_BY_ISLEAF=1  CONNECT BY  WBS_PARENT_ID= PRIOR WBS_ID
              START WITH WBS_ID='$wbs' ORDER SIBLINGS BY WBS_PARENT_ID ")->result();
    }


}
