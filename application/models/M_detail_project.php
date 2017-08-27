<?php
Class M_detail_project extends CI_Model{
  public function activity($project_id,$user_id,$activity,$ip,$date )
  {
    $id = $this->db->query("select NVL(max(cast(ID as int))+1, 1) as NEW_ID from ACTIVITY")->row()->NEW_ID;
    $this->db->query("INSERT INTO ACTIVITY (
      ID,
      PROject_id,
      USER_ID,
      ACTIVITY,
      IP,
      ACTIVITY_TIME
    )
    VALUES
    (
      '".$id."',
      '".$project_id."',
      '".$user_id."',
      '".$activity."',
      '".$ip."',
      to_date('".$date."','YYYY-MM-DD HH24:MI:SS')
      )");
    }
    function getWP($project,$wbs){

    }
    function getHistory($project){
      return $this->db->query("select a.*,b.wbs_name,c.user_name from capture_wbs a inner join wbs b on a.wbs_id=b.wbs_id inner join users c on a.user_id=c.user_id where a.project_id='$project' order by DATE_CAP")->result();
    }
    function endsWith($haystack, $needle)  {
      $length = strlen($needle);
      if ($length == 0) {
        return true;
      }

      return (substr($haystack, -$length) === $needle);
    }
    function removeWBS($wbs){
      $this->db->query("Delete from wbs_pool where wbs_id='".$wbs."'");
      $this->db->query("Delete from wbs where wbs_id='".$wbs."'");
    }
    function UpdatePercentWBS($data){
      //echo $data['WBS_ID'];
      $this->db->query("update wbs set WORK_PERCENT_COMPLETE='".$data['WORK_PERCENT_COMPLETE']."' where wbs_id='".$data['WBS_ID']."'");
      $WBS_ID=$data['WBS_ID'];
      $wbsdata=$this->db->query("select * from wbs where wbs_id='$WBS_ID'")->row();
      if($wbsdata->WORK_COMPLETE==null||$wbsdata->WORK_COMPLETE==0){
        $this->db->query("update wbs set WORK_COMPLETE=".$wbsdata->DURATION."*1*8 where wbs_id='".$WBS_ID."'");
      }
      $check=$this->db->query("select count(cap_id) as CHECK_CAP from capture_wbs where wbs_id='$WBS_ID'")->row()->CHECK_CAP;
      if($check<1){
        $capId=$this->db->query("select nvl(max(cap_id)+1,1) as CAP_ID from capture_wbs")->row()->CAP_ID;
        $this->db->query("insert into capture_wbs VALUES
        ('".$capId."',TO_DATE('".$data['DATE']."','DD/MM/YYYY'),'".$WBS_ID."','".$wbsdata->PROJECT_ID."','".$data['DESCRIPTION']."','".$data['USER_ID']."','".$data['WORK_PERCENT_COMPLETE']."','".$wbsdata->PROGRESS_WBS."')
        ");
      }else{
        $capId=$this->db->query("select CAP_ID from capture_wbs where wbs_id='$WBS_ID'")->row()->CAP_ID;
        $this->db->query("update capture_wbs set date_cap=TO_DATE('".$data['DATE']."','DD/MM/YYYY'), description='".$data['DESCRIPTION']."',user_id='".$data['USER_ID']."',
        work_percent_complete='".$data['WORK_PERCENT_COMPLETE']."' where CAP_ID='".$capId."' ");
      }
      $detCapId=$this->db->query("select nvl(max(detail_cap_id)+1,1) as DETAIL_CAP_ID from detail_capture")->row()->DETAIL_CAP_ID;
      $this->db->query("insert into detail_capture VALUES
      ('".$detCapId."',TO_DATE('".$data['DATE']."','DD/MM/YYYY'),'".$WBS_ID."','".$wbsdata->PROJECT_ID."','".$data['DESCRIPTION']."','".$data['USER_ID']."','".$data['WORK_PERCENT_COMPLETE']."','".$wbsdata->PROGRESS_WBS."','".$capId."')
      ");

      $allParent=$this->getAllParentWBS($WBS_ID);
      foreach ($allParent as $ap) {
        $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
        $wc=0;
        $wp=0;
        $allChild=$this->getAllChildWBS($ap->WBS_ID);
        foreach ($allChild as $ac) {
          $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
          $wc=$wc+$works;
          $works_p=$this->db->query("select case
          when (WORK_COMPLETE=0 OR WORK_COMPLETE is null) then 0 when (WORK_PERCENT_COMPLETE=0 or WORK_PERCENT_COMPLETE is null) then round(WORK*100/WORK_COMPLETE,2)  else WORK_PERCENT_COMPLETE END as WP from wbs where wbs_id='$ac->WBS_ID'")->row()->WP;
          if ($works_p>100) {
            $works_p=100;
          }
          $wp=$wp+$works_p;
        }

        $count = count($allChild);
        $wp_total=$wp/$count;
        //echo "alert('".$wp."')";
        if ($wp_total>100) {
          $wp_total=100;
        }
        $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc', WORK_PERCENT_COMPLETE='$wp_total' where wbs_id='$ap->WBS_ID'");
        if($this->endsWith($ap->WBS_ID,'.0')==true){
          $pc=$this->db->query("select WORK_PERCENT_COMPLETE, PROJECT_ID from wbs where wbs_id='$ap->WBS_ID' ")->row();
          if ($pc->WORK_PERCENT_COMPLETE>100) {
            $this->db->query("update projects set project_complete='100' where project_id='$pc->PROJECT_ID' ");
          }else{
            $this->db->query("update projects set project_complete='$pc->WORK_PERCENT_COMPLETE' where project_id='$pc->PROJECT_ID' ");
          }

        }
      }

    }
    function selectWBS($id,$rh_id){
        return $this->db->query("select SUBSTR(WBS_ID, INSTR(wbs_id, '.')+1) as orde,
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
                                             from temporary_wbs
                                              where action = 'create'
                                              and rh_id = '$rh_id'
                                          ) connect by  wbs_parent_id = prior wbs_id
                                          start with wbs_id='$id.0'
                                          order siblings by regexp_substr(orde, '^\D*') nulls first,
                                          to_number(regexp_substr(orde, '\d+'))")->result_array();
    }
    function getAllBU(){
      return $this->db->query("SELECT BU_CODE, BU_ALIAS, BU_NAME FROM P_BU")->result_array();
    }
    function getPV($project){
      $result=0;
      $q=$this->db->query("select * from tb_pv_project where project_id='$project'");
      if($q->num_rows() > 0){
        $result = $q->row()->PV;
      }
      return $result;
    }
    function getEV($project){
      $result=0;
      $q=$this->db->query("select * from tb_ev_project where project_id='$project'");
      if($q->num_rows() > 0){
        $result = $q->row()->EV;
      }
      return $result;
    }
    function getAC($project){
      $result=0;
      $q=$this->db->query("select * from tb_ac_project where project_id='$project'");
      if($q->num_rows() > 0){
        $result = $q->row()->AC;
      }
      return $result;

    }

    function spi_cpi_all(){
    $sql = "select b.bu_name,b.bu_code, b.bu_alias,b.bu_id, round(sum(ev)/count(c.project_id),2) as EV, round(sum(pv)/count(c.project_id),2) as PV, round(sum(AC)/count(c.project_id),2) as AC, case when round(sum(spi)/count(c.project_id),2)<1 and round(sum(spi)/count(c.project_id),2) not in (0) then '0'||round(sum(spi)/count(c.project_id),2) else to_char(round(sum(spi)/count(c.project_id),2)) end as SPI,  round(sum(CPI)/count(c.project_id),2) as CPI
                from
                (select ev, pv, case when pv=0 then 0 else round(ev/pv,2) end as spi,case when ev=0 then 0 else ac end as ac,case when ac=0 then 1 else round(ev/ac,2) end as cpi, a.project_id
                from tb_ev_project a
                left join tb_pv_project b
                on a.project_id=b.project_id
                left join tb_ac_project c on
                a.project_id=c.project_id) a inner join
                projects c on c.project_id=a.project_id
                inner join p_bu b on (b.bu_code=c.bu_code OR b.bu_alias=c.bu_code)
                where project_status='In Progress' and bu_id <> '30' and bu_id <> '34'
                and type_of_effort in (1,2)
                group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id";

    $query = $this->db->query($sql);
    $hasil = $query->result_array();
    return $hasil;

    }


    function getWBSAvailableUser($project,$wbs_id){
      return $this->db->query("
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
          from temporary_wbs_pool 
          inner join resource_pool 
          on temporary_wbs_pool.rp_id=resource_pool.rp_id 
          where wbs_id='$wbs_id')
        group by RESOURCE_POOL.RP_ID, users.user_name,users.email
        ")->result();
      }
      function getWBSselectedUser($project,$wbs_id){
        return $this->db->query("SELECT RESOURCE_POOL.RP_ID, users.user_name,users.email,'no' as rebaseline FROM RESOURCE_POOL
          join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
          join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
          WHERE PROJECT_ID='$project' and RESOURCE_POOL.user_id  in
          (select user_id from wbs_pool inner join resource_pool on wbs_pool.rp_id=resource_pool.rp_id where wbs_id='$wbs_id')
          group by RESOURCE_POOL.RP_ID, users.user_name,users.email
          UNION 
          SELECT RESOURCE_POOL.RP_ID, users.user_name,users.email,'yes' as rebaseline FROM RESOURCE_POOL
          join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
          join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
          WHERE PROJECT_ID='$project' and RESOURCE_POOL.user_id  in
          (select user_id from temporary_wbs_pool inner join resource_pool on temporary_wbs_pool.rp_id=resource_pool.rp_id where wbs_id='$wbs_id')
          group by RESOURCE_POOL.RP_ID, users.user_name,users.email")->result();
        }
        //Get Project Detail
        function getProjectDetail($id){
          return $this->db->get_where('V_PROJECT_DESC', array('PROJECT_ID' => $id))->row_array();
        }
        function updateParentDate($type,$id,$date){
          if($type=='start'){
            $this->db->query("UPDATE  WBS SET START_DATE=to_date('".$date."','YYYY-MM-DD') WHERE WBS_ID='".$id."'");
          }elseif ($type=='end') {
            $this->db->query("UPDATE  WBS SET FINISH_DATE=to_date('".$date."','YYYY-MM-DD') WHERE WBS_ID='".$id."'");
          }
          $this->updateNewDuration($id);
        }
        function getWBSselected($id){
          return $this->db->query("SELECT * FROM WBS WHERE WBS_ID='".$id."'")->row();
        }
        function getAllParentWBS($id){
          return $this->db->query("SELECT CONNECT_BY_ISLEAF AS LEAF, WBS.*, LEVEL
            FROM WBS where WBS_ID NOT IN ('".$id."') CONNECT BY  WBS_ID=PRIOR WBS_PARENT_ID
            START WITH WBS_ID='".$id."' ORDER SIBLINGS BY WBS_PARENT_ID")->result();
          }
          public function insertWBS($data, $project_id){
            $id = $this->db->query("select NVL(max(cast(ID as int))+1, 1)  as NEW_ID from WBS_PROJECT where PROJECT_ID=".$project_id." ")->row()->NEW_ID;
            $sql = "INSERT INTO WBS
            (
              WBS_ID,
              WBS_PARENT_ID,
              PROJECT_ID,
              WBS_NAME,
              START_DATE,
              FINISH_DATE)
              VALUES
              (
                '".$data['WBS_ID'].".".$id."',
                '".$data['WBS_PARENT_ID']."',
                '".$data['WBS_ID']."',
                '".$data['WBS_NAME']."',
                ".$data['START_DATE'].",
                ".$data['FINISH_DATE']."
                )";
                $q = $this->db->query($sql);
                return $data['WBS_ID'].".".$id;
              }
              function updateNewDuration($wbs){
                $dur=$this->db->query("select COUNT_DURATION from v_countduration_wbs where wbs_id='$wbs'")->row()->COUNT_DURATION;
                $this->db->query("update wbs set duration='$dur' where wbs_id='$wbs'");
                $allParent=$this->getAllParentWBS($wbs);
                foreach ($allParent as $ap) {
                  $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
                  $wc=0;
                  $allChild=$this->getAllChildWBS($ap->WBS_ID);
                  foreach ($allChild as $ac) {
                    $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
                    $wc=$wc+$works;
                  }
                  $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc' where wbs_id='$ap->WBS_ID'");
                }
              }
              function projectProgress($project){
                return $this->db->query("select nvl(sum(total_percent)/count(*),0) as project_progress
                from (select vt.*,w.work_percent_complete, case when (w.work_percent_complete is not null AND w.work_percent_complete !=0) then w.work_percent_complete else vt.work_percent_complete end as total_percent,
                connect_by_isleaf from v_target_entry vt
                inner join  wbs w on w.wbs_id=vt.wbs_id where connect_by_isleaf=1
                connect by wbs_parent_id=prior w.wbs_id start with w.wbs_id='$project.0')")->row()->PROJECT_PROGRESS;
              }
              public function insertWBSPool($data, $RP_ID, $WP_ID, $project_id){
                $id = $this->db->query("select NVL(max(cast(WBS_ID as int))+1, 1) as NEW_ID from WBS_PROJECT where PROJECT_ID=".$project_id." ")->row()->NEW_ID;
                $this->db->set('RP_ID', $RP_ID);
                $this->db->set('WP_ID', $WP_ID);
                $this->db->set('WBS_ID', $data['WBS_ID'].'.'.$id);
                $this->db->insert("WBS_POOL");
              }

              function getMaxWPID(){
                return $this->db->query("select max(WP_ID)+1 as WP_ID from WBS_POOL")->row()->WP_ID;
              }
              function getMaxRPID(){
                return $this->db->query("select max(RP_ID)+1 as RP_ID from WBS_POOL")->row()->RP_ID;
              }


              public function Edit_WBS(
                $WBS_ID,
                $WBS_PARENT_ID,
                $PROJECT_ID,
                $WBS_NAME,
                $START_DATE,
                $FINISH_DATE){

                  $sql = "UPDATE WBS SET
                  WBS_PARENT_ID='".$WBS_PARENT_ID."',
                  PROJECT_ID='".$PROJECT_ID."',
                  WBS_NAME='".$WBS_NAME."',
                  "."START_DATE=to_date('".$START_DATE."','yyyy-mm-dd'),
                  FINISH_DATE=to_date('".$FINISH_DATE."','yyyy-mm-dd')
                  WHERE WBS_ID='".$WBS_ID."'
                  ";
                  $q = $this->db->query($sql);
                  $allParent=$this->getAllParentWBS($WBS_ID);
                  foreach ($allParent as $ap) {
                    $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
                    $wc=0;
                    $wp=0;
                    $allChild=$this->getAllChildWBS($ap->WBS_ID);
                    foreach ($allChild as $ac) {
                      $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
                      $wc=$wc+$works;
                      $works_p=$this->db->query("select case
                      when (WORK_COMPLETE=0 OR WORK_COMPLETE is null) then 0 when (WORK_PERCENT_COMPLETE=0 or WORK_PERCENT_COMPLETE is null) then round(WORK*100/WORK_COMPLETE,2)  else WORK_PERCENT_COMPLETE END as WP from wbs where wbs_id='$ac->WBS_ID'")->row()->WP;
                      if ($works_p>100) {
                        $works_p=100;
                      }
                      $wp=$wp+$works_p;
                    }
                    $count = count($allChild);
                    $wp_total=$wp/$count;
                    //echo "alert('".$wp."')";
                    if ($wp_total>100) {
                      $wp_total=100;
                    }
                    $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc', WORK_PERCENT_COMPLETE='$wp_total' where wbs_id='$ap->WBS_ID'");
                    if($this->endsWith($ap->WBS_ID,'.0')==true){
                      $pc=$this->db->query("select WORK_PERCENT_COMPLETE, PROJECT_ID from wbs where wbs_id='$ap->WBS_ID' ")->row();
                      if ($pc->WORK_PERCENT_COMPLETE>100) {
                        $this->db->query("update projects set project_complete='100' where project_id='$pc->PROJECT_ID' ");
                      }else{
                        $this->db->query("update projects set project_complete='$pc->WORK_PERCENT_COMPLETE' where project_id='$pc->PROJECT_ID' ");
                      }

                    }
                  }

                }

                function getProjectAvailablity($iwo){
                  $result;
                  $sql = "select * from PROJECTS where PROJECT_ID='".$iwo."'";
                  $q = $this->db->query($sql);
                  if($q->num_rows() > 0){
                    $result = $q->row();
                  }
                  return $result;
                }

                public function deleteWBS($id){
                  $this->db->delete('WBS', array('WBS_ID' => $id));
                }


                public function deleteRPmember($id){
                  $this->db->delete('RESOURCE_POOL', array('RP_ID' => $id));
                }

                function editWBS($data){
                  $this->db->where('WBS_ID', $data['WBS_ID']);
                  $this->db->update('WBS', $data);
                }

                function getBU($id){
                  $sql = "SELECT BU_ID FROM P_BU WHERE BU_CODE='".$id."' or BU_ALIAS='".$id."'";
                  $q = $this->db->query($sql);
                  if($q->num_rows() > 0){
                    $result = $q->row()->BU_ID;
                    return $result;
                  }
                }
                function getBURelated($id){
                    $sql = "select related_bu from projects join p_bu 
                            on p_bu.bu_code=projects.bu 
                            WHERE BU_CODE='".$id."' or BU_ALIAS='".$id."'";
                    $q = $this->db->query($sql);
                    if($q->num_rows() > 0){
                        $result = $q->row()->BU_ID;
                        return $result;
                    }
                }
                function getAssignedTeam($project,$wbs){
                  return $this->db->query("SELECT RESOURCE_POOL.*,USERS.USER_NAME FROM RESOURCE_POOL
                    join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
                    join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
                    WHERE PROJECT_ID='$project'
                    and USERS.USER_ID not in (select user_id from wbs_pool wp,
                      resource_pool rp where rp.rp_id=wp.rp_id and wbs_id='$wbs')")->result_array();
                    }
                    function getBUCode($GetBUCodeProject){
                      $sql = "SELECT BU_CODE FROM P_BU WHERE BU_CODE='".$GetBUCodeProject."'";
                      $q = $this->db->query($sql);
                      if($q->num_rows() > 0){
                        $result = $q->row()->BU_CODE;
                        return $result;
                      }
                    }

                    function GetBUCodeProject($bu){
                      $sql = "SELECT BU_CODE FROM PROJECTS where PROJECT_ID='".$bu."'";
                      $q = $this->db->query($sql);
                      if($q->num_rows() > 0){
                        $result = $q->row()->BU_CODE;
                        return $result;
                      }
                    }

                    function getParentID($id){
                      /*
                      $result = array();
                      $sql = "select wbs_id, wbs_name from wbs where PROJECT_ID='".$id."' connect by  wbs_parent_id= prior wbs_id start with wbs_id='".$id.".0' order siblings by wbs_parent_id";
                      $q = $this->db->query($sql);

                      if($q->num_rows() > 0){
                      $result = $q->result();
                    }
                    */
                    //return $result;
                    return $this->db->query("select wbs_id, wbs_name from wbs where PROJECT_ID='".$id."' connect by  wbs_parent_id= prior wbs_id start with wbs_id='".$id.".0' order siblings by wbs_parent_id")->result_array();
                  }
                  function removeAssignement(){
                    $wbs=$this->input->post('WBS_ID');
                    $member=$this->input->post('MEMBER');

                    //delete member from wbs_pool
                    $this->db->where('RP_ID', $member);
                    $this->db->where('WBS_ID', $wbs);
                    $this->db->delete("WBS_POOL");


                    //count jumlah member di task
                    $res=$this->db->query("select count(rp_id) as RES from wbs_pool where wbs_id='$wbs'")->row()->RES;
                    //update resource wbs same as $res
                    $this->db->query("update wbs set resource_wbs=$res where wbs_id='$wbs'");
                    $allParent=$this->getAllParentWBS($wbs);
                    //print_r($allParent);
                    //die;

                    //Recalculation Work Complete Hours
                    foreach ($allParent as $ap) {
                      $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
                      $wc=0;
                      $allChild=$this->getAllChildWBS($ap->WBS_ID);
                      foreach ($allChild as $ac) {
                        $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
                        $wc=$wc+$works;
                      }
                      $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc' where wbs_id='$ap->WBS_ID'");
                    }
                  }
                  function getAllChildWBS($wbs){
                    return $this->db->query("SELECT CONNECT_BY_ISLEAF AS LEAF, WBS.*, LEVEL
                      FROM WBS where WBS_ID NOT IN ('$wbs') and CONNECT_BY_ISLEAF=1  CONNECT BY  WBS_PARENT_ID= PRIOR WBS_ID
                      START WITH WBS_ID='$wbs' ORDER SIBLINGS BY WBS_PARENT_ID ")->result();
                    }
                    function postAssignment(){
                      $wbs=$this->input->post('WBS_ID');
                      $member=$this->input->post('MEMBER');


                      $id = $this->db->query("select NVL(max(cast(WP_ID as int))+1, 1) as NEW_ID from WBS_POOL")->row()->NEW_ID;
                      $this->db->set('RP_ID', $member);
                      $this->db->set('WP_ID', $id);
                      $this->db->set('WBS_ID', $wbs);
                      $this->db->insert("WBS_POOL");

                      $res=$this->db->query("select count(rp_id) as RES from wbs_pool where wbs_id='$wbs'")->row()->RES;
                      $dur=$this->db->query("select DURATION as DUR from wbs where wbs_id='$wbs'")->row()->DUR;
                      $this->db->query("update wbs set resource_wbs=$res, WORK_COMPLETE=$dur*$res*8 where wbs_id='$wbs'");
                      $allParent=$this->getAllParentWBS($wbs);
                      foreach ($allParent as $ap) {
                        $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
                        $wc=0;
                        $allChild=$this->getAllChildWBS($ap->WBS_ID);
                        foreach ($allChild as $ac) {
                          $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
                          $wc=$wc+$works;
                        }
                        $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc' where wbs_id='$ap->WBS_ID'");
                      }
                    }
                    function getDataProject($id){
                      $query = $this->db->query("
                      SELECT b.user_name,c.prof_name, b.email , b.last_login FROM RESOURCE_POOL a
                      join USERS b on a.USER_ID=b.USER_ID
                      join PROFILE c ON c.PROF_ID=b.PROF_ID
                      WHERE PROJECT_ID='".$id."'
                      ");
                      $hasil = $query->result_array();
                      return $hasil;
                    }
                    function getDataProject2($id){
                      $query = $this->db->query("
                      SELECT * FROM RESOURCE_POOL
                      join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
                      join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
                      WHERE PROJECT_ID='".$id."'
                      ");
                      $hasil = $query->result_array();
                      return $hasil;
                    }

                    function getAllUserBU($bu_id){
                      $query = $this->db->query("select * from users where BU_ID='".$bu_id."'");
                      $hasil = $query->result_array();
                    }

                    function getIWO($project_id){
                      $sql = "SELECT IWO_NO FROM PROJECTS WHERE PROJECT_ID='".$project_id."'";
                      $q = $this->db->query($sql);
                      if($q->num_rows() > 0){
                        $result = $q->row()->IWO_NO;
                        return $result;
                      }
                    }

                    function GetDataTask($project_id){
                      //return $this->db->get_where($table,$where);
                      $result = array();
                      $sql = "select * from WBS where PROJECT_ID='".$project_id."'";
                      $q = $this->db->query($sql);

                      if($q->num_rows() > 0){
                        $result = $q->result();
                      }
                      return $result;
                    }

                    function selectWBS2(){
                      $result = array();
                      $sql = "select * from WBS ";
                      $q = $this->db->query($sql);

                      if($q->num_rows() > 0){
                        $result = $q->result();
                      }
                      return $result;
                    }

                    function selectWB(){
                      $sql = "select WBS_ID,WBS_NAME,START_DATE,WBS_PARENT_ID from WBS";
                      $query = $this->db->query($sql);
                      $hasil = $query->result_array();
                      return $hasil;

                    }

                    function Upload($data){
                      //$sql="INSERT INTO PROJECT_DOC
                      //(DOC_ID,DATE,PROJECT_ID,DOC_NAME,URL,UPLOAD_BY,DOC_DESC) VALUES (
                      /*
                      $date =$data['DATE_UPLOAD'];
                      $this->db->set('DOC_ID',$data['DOC_ID']);
                      $this->db->set('DATE_UPLOAD',$data['DATE_UPLOAD']);
                      $this->db->set('PROJECT_ID',$data['PROJECT_ID']);
                      $this->db->set('DOC_NAME',$data['DOC_NAME']);
                      $this->db->set('URL',"/assets/image/".$data['URL']);
                      $this->db->set('UPLOAD_BY',$data['UPLOAD_BY']);
                      $this->db->set('DOC_DESC',$data['DOC_DESC']);
                      $this->db->insert('PROJECT_DOC');
                      *///echo($sql);
                      $sql = "INSERT INTO PROJECT_DOC(
                        DOC_ID,
                        DATE_UPLOAD,
                        PROJECT_ID,
                        DOC_NAME,
                        URL,
                        UPLOAD_BY,
                        DOC_DESC)VALUES(
                          '".$data['DOC_ID']."',
                          ".$data['DATE_UPLOAD'].",
                          '".$data['PROJECT_ID']."',
                          '".$data['DOC_NAME']."',
                          '".$data['URL']."',
                          '".$data['UPLOAD_BY']."',
                          '".$data['DOC_DESC']."')";
                          $q = $this->db->query($sql);

                        }

                        function deleteFile($doc_id){
                          $this->db->delete('PROJECT_DOC', array('DOC_ID' => $doc_id));
                        }

                        function deleteWBSID($id){
                          $this->db->delete('WBS', array('WBS_ID' => $id));
                        }

                        function deleteWBSPoolID($id){
                          $this->db->delete('WBS_POOL', array('WBS_ID' => $id));
                        }

                        function getProject($doc_id){
                          $sql = "select PROJECT_ID from PROJECT_DOC where DOC_ID='".$doc_id."'";
                          $q = $this->db->query($sql);
                          if($q->num_rows() > 0){
                            $result = $q->row()->PROJECT_ID;
                            return $result;
                          }
                        }

                        function getProjectTask($id){
                          $sql = "select PROJECT_ID from WBS where WBS_ID='".$id."'";
                          $q = $this->db->query($sql);
                          if($q->num_rows() > 0){
                            $result = $q->row()->PROJECT_ID;
                            return $result;
                          }
                          else{
                              $sql = "select PROJECT_ID from TEMPORARY_WBS where WBS_ID='".$id."'";
                              $q = $this->db->query($sql);
                              if($q->num_rows() > 0){
                                  $result = $q->row()->PROJECT_ID;
                                  return $result;
                              }
                          }
                        }
                        function getProjecCalc($project){
                          return $this->db->query("select ev, pv, case when pv=0 then round(ev/1,2) else round(ev/pv,2) end as spi,case when ev=0 then 0 else ac end as ac,case when ac=0 then round(ev/1,2) else round(ev/ac,2) end as cpi, a.project_id
                          from tb_ev_project a
                          left join tb_pv_project b
                          on a.project_id=b.project_id
                          left join tb_ac_project c on
                          a.project_id=c.project_id
                          where b.project_id='$project'
                          ")->row();
                        }
                        function getRPProject($id){
                          $sql = "select PROJECT_ID from RESOURCE_POOL where RP_ID='".$id."'";
                          $q = $this->db->query($sql);
                          if($q->num_rows() > 0){
                            $result = $q->row()->PROJECT_ID;
                            return $result;
                          }
                        }

                        function  getProjectIssue($id){
                          $sql = "select PROJECT_ID from manage_issue where issue_id='".$id."'";
                          $q = $this->db->query($sql);
                          if($q->num_rows() > 0){
                            $result = $q->row()->PROJECT_ID;
                            return $result;
                          }
                        }

                        function getProjectName($id){
                          $sql = "select PROJECT_NAME from PROJECTS where PROJECT_ID='".$id."'";
                          $q = $this->db->query($sql);
                          if($q->num_rows() > 0){
                            $result = $q->row()->PROJECT_NAME;
                            return $result;
                          }
                        }

                        function getAllFile($var){
                          $query = $this->db->query("SELECT * FROM PROJECT_DOC join USERS
                            ON PROJECT_DOC.UPLOAD_BY= USERS.USER_ID
                            WHERE PROJECT_ID='".$var."'");
                            $hasil = $query->result_array();
                            return $hasil;
                          }

                          function getMaxNumber(){
                            return $this->db->query("select  NVL(max(cast(DOC_ID as int))+1, 1) as NEW_ID from PROJECT_DOC")->row()->NEW_ID;

                          }

                          function getMaxNumberWBS(){
                            return $this->db->query("select max(WBS_ID)+1 as NEW_ID from WBS")->row()->NEW_ID;
                          }

                          function getMaxNumberResource(){
                            return $this->db->query("select max(RP_ID)+1 as NEW_ID from RESOURCE_POOL")->row()->NEW_ID;
                          }

                          function getProject_Id($iwo){
                            $result;
                            $sql = "select PROJECT_ID from PROJECTS where IWO_NO='".$iwo."'";
                            $q = $this->db->query($sql);
                            if($q->num_rows() > 0){
                              $result = $q->row()->PROJECT_ID;
                            }
                            return $result;
                          }

                          function getAllDataProject($id){
                            $query = $this->db->query("SELECT p.*, us.USER_NAME,b.BU_NAME FROM PROJECTS p, USERS us, P_BU b
                              WHERE (b.BU_CODE=p.BU_CODE OR b.BU_ALIAS=p.BU_CODE) AND p.PM_ID=us.USER_ID AND PROJECT_ID='".$id."'");
                              $hasil = $query->result_array();
                              return $hasil;
                              //echo($query);
                            }
                            function getAllDataProject2($project){
                              return $this->db->query("select to_char(SCHEDULE_START,'YYYY-mm-dd') as SCHEDULE_START,to_char(SCHEDULE_END,'YYYY-mm-dd') as SCHEDULE_END from projects where project_id='$project'")->row_array();
                            }

                            function get_resources ($id){

                              $query = $this->db->query("SELECT * FROM V_CALCULATE_TASK_PER_USER2 WHERE wbs_id='".$id."'");

                              $hasil = $query->result();
                              return $hasil;
                              //return $this->db->query("SELECT * FROM V_CALCULATE_TASK_PER_USER ")->result();
                              //return $id;
                              //return "SELECT * FROM V_CALCULATE_TASK_PER_USER WHERE wbs_id='".$id."'";
                            }

                            function getLink($DOC_ID){
                              $query = $this->db->query("SELECT URL FROM PROJECT_DOC WHERE DOC_ID='".$DOC_ID."'");
                              $hasil = $query->result_array();
                              return $hasil;
                              //echo($hasil);
                            }

                            function deleteTaskID($doc_id){
                              $this->db->delete('WBS', array('ID' => $doc_id));
                            }

                            function getMaxNumberRHID(){
                              return $this->db->query("select max(RH_ID)+1 as RH_ID from REBASELINE_HISTORY")->row()->RH_ID;
                            }

                            function rebaseline_insert($data){


                              $this->db->insert('REBASELINE_HISTORY', $data);


                            }

                            function rebaseline_update($data){
                              $this->db->where('PROJECT_ID', $data['PROJECT_ID']);
                              $this->db->update('PROJECT', $data);
                            }

                            function something($project){
                              return $this->db->query("select wbs.wbs_id, pr.BU_CODE, wbs.wbs_name, wbs.project_id,to_char(wbs.start_date,'yyyy-mm-dd') as start_date,res, level
                              from wbs left join (select w.wbs_id as wbs_id, count(wp_id) as res from wbs w
                              left join wbs_pool p on w.wbs_id=p.wbs_id group by w.wbs_id) w on wbs.wbs_id=w.wbs_id left join projects pr on wbs.project_id=pr.project_id
                              WHERE connect_by_isleaf=1 AND  START_date <= sysdate
                              connect by wbs_parent_id= prior wbs.wbs_id start with wbs.wbs_id='$project.0' order siblings by wbs_parent_id")->result();
                            }
                            function something2($project){
                              return $this->db->query("select count(MAX_ENTRY) as ENTRIES, sum(MAX_ENTRY), WBS_ID,a.PROJECT_ID, bu_code  from (select max(hour_total) as MAX_ENTRY, wbs_pool.wbs_id, wbs_name,ts_date, PROJECT_ID FROM timesheet INNER JOIN wbs_pool ON timesheet.wp_id = wbs_pool.wp_id
                              INNER JOIN wbs ON wbs.wbs_id = wbs_pool.wbs_id
                              GROUP BY wbs_pool.wbs_id,ts_date, wbs_name,wbs.project_id) a left join projects pr on a.project_id=pr.project_id where a.PROJECT_ID='$project' group by WBS_ID, a.PROJECT_ID,bu_code")->result();
                            }

                            function cpi1($project){
                              return $this->db->query("select wbs.wbs_id,bu_code, wbs.wbs_name, wbs.project_id,to_char(wbs.start_date,'yyyy-mm-dd') as start_date, case when res=0 then 1 else res end as res , level
                              from wbs left join (select w.wbs_id as wbs_id, count(wp_id) as res from wbs w
                              left join wbs_pool p on w.wbs_id=p.wbs_id group by w.wbs_id) w on wbs.wbs_id=w.wbs_id
                              inner join projects pr on wbs.project_id=pr.project_id
                              WHERE connect_by_isleaf=1 AND  START_date <= sysdate
                              connect by wbs_parent_id= prior wbs.wbs_id start with wbs.wbs_id='$project.0' order siblings by wbs_parent_id")->result();
                            }
                            function cpi2($project){
                              return $this->db->query("
                              select count(hour_total) as count_ENTRY, wbs_pool.wbs_id, wbs_name,bu_code, wbs.PROJECT_ID
                              FROM timesheet INNER JOIN wbs_pool ON timesheet.wp_id = wbs_pool.wp_id
                              INNER JOIN wbs ON wbs.wbs_id = wbs_pool.wbs_id
                              inner join projects pr on wbs.project_id=pr.project_id
                              where wbs.project_id='$project'
                              GROUP BY wbs_pool.wbs_id, wbs_name,wbs.project_id, bu_code")->result();
                            }

                            function selectemail($user_id) {
                            return $this->db->query("SELECT * from users where user_id='" . $user_id . "'")->row()->EMAIL;
                          }

                          function selectProjectName($project_id) {
                             return $this->db->query("SELECT * from projects where project_id='" . $project_id . "'")->row()->PROJECT_NAME;
                           }


                            function updateProgressDeleteTask($wbs_id){
                              //  $wbs=$this->db->query("SELECT WBS_ID from WBS_POOL WHERE WP_ID='".$wp."'")->row()->WBS_ID;

                              //$work=$this->db->query("select sum(hour_total) as WORK_H, wbs_id from user_timesheet where wbs_id='$wbs'  group by wbs_id")->row()->WORK_H;
                              //$wc=$this->db->query("select work_complete from wbs where wbs_id='$wbs'")->row()->WORK_COMPLETE;
                              //$this->db->query("update wbs set work='$work', progress_wbs=$work*100/$wc where wbs_id='$wbs'");

                              $allParent=$this->getAllParentWBS($wbs_id);
                              //deleteWBSPool & deleteWBSID
                              $this->db->delete('WBS_POOL', array('WBS_ID' => $wbs_id));
                              $this->db->delete('WBS', array('WBS_ID' => $wbs_id));

                              foreach($allParent as $ap){
                                $h=0;
                                $resAp=$this->db->query("select nvl(sum(resource_wbs),0) as RES from wbs where wbs_parent_id='$ap->WBS_ID'")->row()->RES;
                                $wc=0;
                                $wp=0;
                                $allChild=$this->getAllChildWBS($ap->WBS_ID);
                                foreach($allChild as $ac){
                                  $c_work=$this->db->query("SELECT nvl(work,0) as WORK from wbs where wbs_id='$ac->WBS_ID'")->row()->WORK;
                                  $h=$h+$c_work;
                                  $works=$this->db->query("select WORK_COMPLETE as WC from wbs where wbs_id='$ac->WBS_ID'")->row()->WC;
                                  $wc=$wc+$works;
                                  $works_p=$this->db->query("select case
                                  when (WORK_COMPLETE=0 OR WORK_COMPLETE is null) then 0 when (WORK_PERCENT_COMPLETE=0 or WORK_PERCENT_COMPLETE is null) then round(WORK*100/WORK_COMPLETE,2)  else WORK_PERCENT_COMPLETE END as WP from wbs where wbs_id='$ac->WBS_ID'")->row()->WP;
                                  if ($works_p>100) {
                                    $works_p=100;
                                  }
                                  $wp=$wp+$works_p;
                                }
                                $count = count($allChild);
                                $wp_total=$wp/$count;
                                if ($wp_total>100) {
                                  $wp_total=100;
                                }
                                $wcAp=$this->db->query("select case when work_complete is null  then 1 when work_complete=0 then 1 else to_number(work_complete) end as work_complete from wbs where wbs_id='$ap->WBS_ID'")->row()->WORK_COMPLETE;
                                if ($h*100/$wcAp>100) {
                                  $this->db->query("update wbs set work='$h',progress_wbs=100 where wbs_id='$ap->WBS_ID'");
                                }else{
                                  $this->db->query("update wbs set work='$h',progress_wbs=$h*100/$wcAp where wbs_id='$ap->WBS_ID'");
                                }

                                $this->db->query("update wbs set resource_wbs=$resAp,WORK_COMPLETE='$wc', WORK_PERCENT_COMPLETE='$wp_total' where wbs_id='$ap->WBS_ID'");
                                if($this->endsWith($ap->WBS_ID,'.0')==true){
                                  $pc=$this->db->query("select WORK_PERCENT_COMPLETE, PROJECT_ID from wbs where wbs_id='$ap->WBS_ID' ")->row();
                                  $this->db->query("update projects set project_complete='$pc->WORK_PERCENT_COMPLETE' where project_id='$pc->PROJECT_ID' ");
                                }
                              }
                            }
    public function clearAll($project){
  $this->db->query("UPDATE wbs SET wbs_desc = NULL, work = 0, milestone = NULL, work_complete = 0, work_percent_complete = 0, progress_wbs = 0, resource_wbs = 0 WHERE wbs_id = '$project.0'");
}

    public function insertWBSTemp($data, $project_id,$rh_id){

        $id = $this->db->query("select NVL(max(cast(ID as int))+1, 1)  as NEW_ID from 
                                (select SUBSTR(WBS_ID, INSTR(wbs_id, '.')+1) as ID,PROJECT_ID from wbs
                                UNION 
                                SELECT SUBSTR(WBS_ID, INSTR(wbs_id, '.')+1) as ID,PROJECT_ID from temporary_wbs where rh_id = '$rh_id') where PROJECT_ID=".$project_id." ")->row()->NEW_ID;
        $sql = "INSERT INTO TEMPORARY_WBS
            (
              WBS_ID,
              WBS_PARENT_ID,
              PROJECT_ID,
              WBS_NAME,
              START_DATE,
              FINISH_DATE,
              IS_VALID,
              RH_ID,ACTION)
              VALUES
              (
                '".$data['WBS_ID'].".".$id."',
                '".$data['WBS_PARENT_ID']."',
                '".$data['WBS_ID']."',
                '".$data['WBS_NAME']."',
                ".$data['START_DATE'].",
                ".$data['FINISH_DATE'].",
                1,
                ".$rh_id.",
                'create'
                )";
        $q = $this->db->query($sql);
        return $data['WBS_ID'].".".$id;
    }

    public function Edit_WBSTemp($WBS_ID,$WBS_PARENT_ID,$PROJECT_ID,$WBS_NAME,$START_DATE,$FINISH_DATE,$RH_ID){
        /*NOT USED QUERY BECAUSE WE USE TEMPORARY TABLE*/
        /*
         * $sql = "UPDATE WBS SET
                  WBS_PARENT_ID='".$WBS_PARENT_ID."',
                  PROJECT_ID='".$PROJECT_ID."',
                  WBS_NAME='".$WBS_NAME."',
                  "."START_DATE=to_date('".$START_DATE."','yyyy-mm-dd'),
                  FINISH_DATE=to_date('".$FINISH_DATE."','yyyy-mm-dd'),
                  WHERE WBS_ID='".$WBS_ID."'
                  ";*/

        $sqltemp = "insert into temporary_wbs (wbs_id,wbs_parent_id,project_id,wbs_name,start_date,finish_date,is_valid,action,rh_id)
                    values(
                    '$WBS_ID','$WBS_PARENT_ID','$PROJECT_ID','$WBS_NAME',to_date('".$START_DATE."','yyyy-mm-dd'),to_date('".$FINISH_DATE."','yyyy-mm-dd'),1,'update','$RH_ID'
                    )";
        $q = $this->db->query($sqltemp);


    }

    function updateProgressDeleteTaskTemp($wbs_id,$rh_id){
        $this->db->query("insert into temporary_wbs(wbs_id,is_valid,rh_id,action) values('$wbs_id',1,$rh_id,'delete')");
    }

    function removeAssignementTemp(){
    $wbs=$this->input->post('WBS_ID');
    $member=$this->input->post('MEMBER');

    //Assign primary key of wbs pool id to temporary with status delete ,so in the future
    //if rebaseline acc ,calucation will happen
    $action = $this->db->query("insert into temporary_wbs_pool (RP_ID,WBS_ID,IS_VALID,ACTION ) values('$member','$wbs',1,'delete')");
}

    function postAssignmentTemp($rh_id){
        $wbs=$this->input->post('WBS_ID');
        $member=$this->input->post('MEMBER');

        $id = $this->db->query("select NVL(max(cast(WP_ID as int))+1, 1) as NEW_ID from (
                                select WP_ID from WBS_POOL
                                UNION 
                                select WP_ID from TEMPORARY_WBS_POOL)")->row()->NEW_ID;
        $this->db->set('RP_ID', $member);
        $this->db->set('WP_ID', $id);
        $this->db->set('WBS_ID', $wbs);
        $this->db->set('IS_VALID', 1);
        $this->db->set('ACTION', 'create');
        $this->db->set('RH_ID',$rh_id);
        $this->db->insert("TEMPORARY_WBS_POOL");


    }

    function getRebaselineTask($id){
        $query = "select wbs_id,wbs_parent_id,project_id,wbs_name,start_date,
                    finish_date as end_date, duration,work,work_complete as work_total,
                    work_percent_complete, 'yes' as rebaseline, action from temporary_wbs
                    where rh_id = '$id' and action != 'create'";
        return $this->db->query($query)->result_array();
    }

}


