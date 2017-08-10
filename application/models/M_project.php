<?php

class M_project extends CI_Model {

    function _construct() {
        parent::_construct();
        $this->load->database();
    }

    function getProject($id) {
        return $this->db->query("SELECT B.BU_NAME,D.USER_NAME as AM_NAME,D.USER_ID as USER_AM_ID, C.PROJECT_ID,C.PROJECT_NAME,C.PM_ID,C.IWO_NO,C.BU_CODE,C.RELATED_BU,to_char(C.SCHEDULE_START,'YYYY-MM-DD')
    AS SCHEDULE_START,to_char(C.SCHEDULE_END,'YYYY-MM-DD') AS SCHEDULE_END,C.CUR_ID,C.AMOUNT,C.PROJECT_TYPE_ID,C.AM_ID,C.CUST_ID,C.CUST_END_ID,C.PROJECT_STATUS,C.PROJECT_DESC,C.EXCHANGE_RATE,C.MARGIN,C.ACTUAL_START_DATE,C.ACTUAL_START_DATE,C.APPLY_TEMPLATE,C.FUNCTIONAL_AREA,C.PRIORITY,C.TYPE_OF_EFFORT,C.PRODUCT_TYPE,C.VISIBILITY,C.CALCULATION_METHOD,C.TYPE_OF_EXPENSE,C.PROJECT_OVERHEAD,C.ACTUAL_COST,
    C.COST_CENTER,C.COGS,C.ESTIMATED_IRR,C.REAL_IRR,C.PAYBACK_TIME,C.PAYBACK_UNITS,C.COMMENTS,C.CREATED_BY,C.PROJECT_COMPLETE,C.RISK_RATING FROM PROJECTS C LEFT JOIN P_BU B ON C.BU_CODE=B.BU_CODE LEFT JOIN USERS D ON C.AM_ID=D.USER_ID  WHERE PROJECT_ID='" . $id . "'")->row_array();
    }
    function getBUCodeByProjectID($project){
        return $this->db->query("SELECT BU_CODE FROM PROJECTS WHERE PROJECT_ID='$project'")->row();
    }
    function getProjectID($id) {
        return $this->db->query("SELECT B.BU_ID AS BU_ID FROM PROJECTS C LEFT JOIN P_BU B ON C.BU_CODE=B.BU_CODE WHERE PROJECT_ID='" . $id . "'")->row()->BU_ID;
    }
    function getAMAll(){
        return $this->db->query("select user_id,user_name from users")->result_array();
    }
    function getBUID($code) {
        return $this->db->query("SELECT BU_ID FROM P_BU WHERE (BU_ALIAS='" . $code . "' or BU_CODE='" . $code . "')")->row()->BU_ID;
    }

    function getBUCode($bu_id) {
        $result = null;
        $sql = "Select *
    From p_bu where IS_ACTIVE=1 and BU_CODE is not null
    and BU_ID = '" . $bu_id . "'";
        $q = $this->db->query($sql);

        if ($q->num_rows() > 0) {
            $result = $q->row();
        }
        return $result;
    }

    function getProjectType() {
        $result = null;
        $sql = "select * from P_PROJECT_TYPE";
        $q = $this->db->query($sql);

        if ($q->num_rows() > 0) {
            $result = $q->result();
        }
        return $result;
    }

    function getCustomer($cust_id) {
        $result = null;
        $sql = "select * from P_CUSTOMERS WHERE CUSTOMER_ID='" . $cust_id . "'";
        $q = $this->db->query($sql);

        if ($q->num_rows() > 0) {
            $result = $q->row()->CUSTOMER_NAME;

            return $result;
        } else {
            return null;
        }
    }

    function getProjectCat($data) {
        $sql = "SELECT * FROM P_PROJECT_CATEGORY WHERE PROJECT_TYPE='" . $data . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows() > 0) {
            $result = $q->result();
        }
        return $result;
    }

    function getPM($bu) {
        $result = null;
        $sql = "SELECT USER_NAME, USER_ID FROM USERS WHERE BU_ID='".$bu."' AND IS_ACTIVE='1' order by USER_NAME";
        $q = $this->db->query($sql);
        if ($q->num_rows() > 0) {
            $result = $q->result_array();
        }
        return $result;
    }

    function getAM($am) {
        $result = null;
        $sql = "Select USER_NAME, USER_ID from users where user_id='".$am."'  AND IS_ACTIVE='1' order by USER_NAME";
        $q = $this->db->query($sql);
        if ($q->num_rows() > 0) {
            $result = $q->result_array();
        }
        return $result;
    }

    function addProject($userdata) {
        $PROJECT_NAME = $this->input->post('PROJECT_NAME');
        $PM_ID = $this->input->post('PM');
        $IWO_NO = $this->input->post('IWO_NO');
        $BU_CODE = $this->input->post('BU');
        $SCHEDULE_START = $this->input->post('START');
        $SCHEDULE_END = $this->input->post('END');
        $AMOUNT = $this->input->post('AMOUNT');
        $PROJECT_TYPE_ID = $this->input->post('PROJECT_TYPE_ID');
        $AM_ID = $this->input->post('AM_ID');
        $CUST_ID = $this->input->post('CUST_ID');
        $CUST_END_ID = $this->input->post('END_CUST_ID');
        $PROJECT_DESC = $this->input->post('DESC');
        $MARGIN = $this->input->post('MARGIN');
        $TYPE_OF_EFFORT = $this->input->post('TYPE_OF_EFFORT');
        $PRODUCT_TYPE = $this->input->post('PRODUCT_TYPE');
        $VISIBILITY = $this->input->post('VISIBILITY');
        $TYPE_OF_EXPENSE = $this->input->post('TYPE_OF_EXPENSE');
        $PROJECT_STATUS = $this->input->post('PROJECT_STATUS');
        $PROJECT_OVERHEAD = $this->input->post('OVERHEAD');
        $ACTUAL_COST = $this->input->post('ACTUAL_COST');
        $COGS = $this->input->post('COGS');
        $RELATED_BU = $this->input->post('RELATED');
        $CREATED_BY = $userdata['USER_ID'];
        $CUR_ID = 'IDR';
        $CALCULATION_METHOD =1;
        $today = date("Y-m-d");


        $sql = "insert into PROJECTS (PROJECT_ID,
                PROJECT_NAME,
                PM_ID,
                IWO_NO,
                BU_CODE,
                SCHEDULE_START,
                SCHEDULE_END,
                CUR_ID,
                AMOUNT,
                PROJECT_TYPE_ID,
                AM_ID,
                CUST_ID,
                CUST_END_ID,
                PROJECT_STATUS,
                PROJECT_DESC,
                MARGIN,
                TYPE_OF_EFFORT,
                PRODUCT_TYPE,
                VISIBILITY,
                CALCULATION_METHOD,
                TYPE_OF_EXPENSE,
                PROJECT_OVERHEAD,
                ACTUAL_COST,
                COGS,
                RELATED_BU,
                CREATED_BY,
                DATE_CREATED) values 
                ((select NVL(max(cast(PROJECT_ID as int))+1, 1) as NEW_ID from PROJECTS),
                '". $PROJECT_NAME . "',
                '" . $PM_ID . "',
                '" . $IWO_NO . "',
                '" . $BU_CODE . "',
                to_date('" . $SCHEDULE_START . "','yyyy-mm-dd'),
                to_date('" . $SCHEDULE_END . "','yyyy-mm-dd'),
                '". $CUR_ID . "',
                " . $AMOUNT . ",
                '" . $PROJECT_TYPE_ID . "',
                '". $AM_ID . "',
                '" . $CUST_ID . "',
                '" . $CUST_END_ID . "',
                '". $PROJECT_STATUS . "',
                '" . $PROJECT_DESC . "',
                ". $MARGIN . ",
                '". $TYPE_OF_EFFORT . "',
                '" . $PRODUCT_TYPE . "',
                '". $VISIBILITY . "',
                '" . $CALCULATION_METHOD . "',
                '" . $TYPE_OF_EXPENSE . "',
                '". $PROJECT_OVERHEAD . "',
                " . $ACTUAL_COST . ",
                '". $COGS . "',
                '" . $RELATED_BU . "',
                '" . $CREATED_BY . "',
                to_date('" . $today . "','yyyy-mm-dd'))";

            $this->db->query($sql);
            $sql = "Select max(cast(PROJECT_ID as int)) as NEW_ID from projects";
            $q = $this->db->query($sql);
            if ($q->num_rows() > 0) {
                $result = $q->row()->NEW_ID;
            }
            $email=$this->selectemail($PM_ID);
            $rp_id = $this->db->query("select nvl(max(cast(rp_id as int))+1,1) as NEW_ID from resource_pool")->row()->NEW_ID;
            $sql2 = "INSERT INTO RESOURCE_POOL (RP_ID,USER_ID,PROJECT_ID,EMAIL) VALUES ('" . $rp_id . "','" . $PM_ID . "','" . $result . "','" . $email. "')";
            $q2 = $this->db->query($sql2);

            //$project_id=$this->db->query('select PROJECT_ID from PROJECTS WHERE IWO_NO like "%'.$IWO_NO.'%" LIMIT 1')->row()->PROJECT_ID;
            return $result;
    }

    function update($id) {
        $PROJECT_NAME = $this->input->post('PROJECT_NAME');
        $PM_ID = $this->input->post('PM_ID');
        $IWO_NO = $this->input->post('IWO_NO');
        $BU_CODE = $this->input->post('BU');
        $SCHEDULE_START = $this->input->post('START');
        $SCHEDULE_END = $this->input->post('END');
        $AMOUNT = $this->input->post('AMOUNT');
        $PROJECT_TYPE_ID = $this->input->post('PROJECT_TYPE_ID');
        $AM_ID = $this->input->post('AM_ID');
        $CUST_ID = $this->input->post('CUST_ID');
        $CUST_END_ID = $this->input->post('END_CUST_ID');
        $PROJECT_STATUS=$this->input->post('PROJECT_STATUS');
        $PROJECT_DESC = $this->input->post('DESC');
        $MARGIN = $this->input->post('MARGIN');
        $TYPE_OF_EFFORT = $this->input->post('TYPE_OF_EFFORT');
        $PRODUCT_TYPE = $this->input->post('PRODUCT_TYPE');
        $VISIBILITY = $this->input->post('VISIBILITY');
        $TYPE_OF_EXPENSE = $this->input->post('TYPE_OF_EXPENSE');
        $PROJECT_OVERHEAD = $this->input->post('OVERHEAD');
        $ACTUAL_COST = $this->input->post('ACTUAL_COST');
        $COGS = $this->input->post('COGS');
        $RELATED_BU = $this->input->post('RELATED');
        $CALCULATION_METHOD = 1;
        $CUR_ID = 'IDR';
        $sql="UPDATE PROJECTS SET PROJECT_NAME='".$PROJECT_NAME."',"
            . "PM_ID='".$PM_ID."',"
            . "IWO_NO='".$IWO_NO."',"
            . "PROJECT_STATUS='".$PROJECT_STATUS."',"
            . "SCHEDULE_START=to_date('".$SCHEDULE_START."','YYYY-MM-DD'),"
            . "SCHEDULE_END=to_date('".$SCHEDULE_END."','YYYY-MM-DD'),"
            . "CUR_ID='".$CUR_ID."',"
            . "AMOUNT='".$AMOUNT."',"
            . "PROJECT_TYPE_ID='".$PROJECT_TYPE_ID."',"
            . "AM_ID='".$AM_ID."',"
            . "CUST_ID='".$CUST_ID."',CUST_END_ID='".$CUST_END_ID."',
                PROJECT_DESC='".$PROJECT_DESC."',MARGIN='".$MARGIN."',TYPE_OF_EFFORT='".$TYPE_OF_EFFORT."',PRODUCT_TYPE='".$PRODUCT_TYPE."',VISIBILITY='".$VISIBILITY."',CALCULATION_METHOD='".$CALCULATION_METHOD."',TYPE_OF_EXPENSE='".$TYPE_OF_EXPENSE."',PROJECT_OVERHEAD='".$PROJECT_OVERHEAD."',
                ACTUAL_COST='".$ACTUAL_COST."',COGS='".$COGS."',RELATED_BU='".$RELATED_BU."' WHERE PROJECT_ID='".$id."'";

        if($this->db->query($sql)){
            $email=$this->selectemail($PM_ID);
            $rp_id = $this->db->query("select nvl(max(cast(rp_id as int))+1,1) as NEW_ID from resource_pool")->row()->NEW_ID;
            if($rp_id != null || $rp_id != ""){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
        //print_r($sql);

        //$sql2 = "INSERT INTO RESOURCE_POOL (RP_ID,USER_ID,PROJECT_ID,EMAIL) VALUES ('" . $rp_id . "','" . $PM_ID . "','" . $result . "','" . $email. "')";
        //$q2 = $this->db->query($sql2);
    }
    function selectemail($user_id) {
        return $this->db->query("SELECT * from users where user_id='" . $user_id . "'")->row()->EMAIL;
    }
    function addProjectWBS($id,$dur){
        $PROJECT_NAME = $this->input->post('PROJECT_NAME');
        $SCHEDULE_START = $this->input->post('START');
        $SCHEDULE_END = $this->input->post('END');
        $this->db->query("INSERT INTO WBS (PROJECT_ID, WBS_ID, WBS_NAME,
      START_DATE, FINISH_DATE, PROGRESS_WBS, DURATION) VALUES ('".$id."', '".$id.".0',
      '".$PROJECT_NAME."',
      to_date('".$SCHEDULE_START."','YYYY-MM-DD'),
      to_date('".$SCHEDULE_END."','YYYY-MM-DD'), '0', ".$dur.")");
    }
    function checkIWO($IWO) {
        return $this->db->query("select count(*) as C from PROJECTS where IWO_NO like '%" . $IWO . "%'")->row()->C;
    }

    function getUsersProject($id) {
        return $this->db->query("SELECT   distinct project_id, project_name,bu_name, bu_code,project_complete,
            project_status, project_desc, created_by
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
                    where user_id='" . $id . "' or created_by='" . $id . "'")->result_array();
    }

    function getWBS($project) {
        return $this->db->query("select WBS_ID AS id, WBS_PARENT_ID AS parent, WBS_NAME AS text,
      TO_CHAR(START_DATE, 'YYYY-MM-DD') as start_Date, DURATION as duration,
      PROGRESS_WBS as progress from wbs where project_id='" . $project . "' order by ID")->result_array();
    }

    function test() {
        return $this->db->query("select * from wbs")->result_array();
    }
    function verifyIWO($iwo){
        return $this->db->query("select count(*) as CNT from PROJECTS where IWO_NO like '%".$iwo."%' and IWO_NO not in ('none')")->row()->CNT;
    }

    function searchBuCode($bu_name){
        $bu_jadi = [];

        foreach ($bu_name as $name){
            $temp = $this->db->query("select BU_CODE from p_bu where bu_name = '$name'")->row();
            array_push($bu_jadi,array(bu_name=>$name,bu_code=>$temp->BU_CODE));

        }
        return $bu_jadi;
    }

    function getBuBasedCode($bucode){
        $result = $this->db->query("select bu_name,bu_code from p_bu where bu_code = '$bucode'")->result_array();
        return $result;

    }

    function getPMBuCode($bu_code) {
        $result = null;

        $findbu = $this->db->query("select bu_id from p_bu where bu_code = '$bu_code'")->row();

        $bu = $findbu->BU_ID;

        $sql = "SELECT USER_NAME, USER_ID FROM USERS WHERE BU_ID='".$bu."' AND IS_ACTIVE='1' order by USER_NAME";
        $q = $this->db->query($sql);
        if ($q->num_rows() > 0) {
            $result = $q->result_array();
        }
        return $result;
    }

}
