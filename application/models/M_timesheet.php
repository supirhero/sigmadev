<?php

Class M_timesheet extends CI_Model{

    /*  function editMenu($data){
    $this->db->where('id', $id);
    $this->db->update('mytable', $data);
  }*/

    function selectTimesheet_bydate($user_id,$date){
        //ada perubahan

        //echo "date : $date<br>";
        $query = $this->db->query("
          SELECT *
          FROM
          (SELECT *
          FROM USER_TIMESHEET_NEW where ts_date= to_date('$date','yyyy-mm-dd')
          ORDER BY ts_date DESC)
          WHERE user_id ='".$user_id."'");
        $hasil = $query->result_array();
        //echo $this->db->last_query();
        //die;
        return $hasil;

    }

    function selectTimesheet_bymonth($user_id,$month,$year){
        //ada perubahan

        //ada perubahan
        $now = date('Y-m-d');
        $past = date('Y-m-d', strtotime('this month'));
        $query = $this->db->query("select distinct TS_ID,WP,USER_ID,USER_NAME,TS.PROJECT_ID,MESSAGE,SUBJECT,TS_DATE,HOUR_TOTAL,latitude,longitude,is_approved,month,project_name,submit_date,tahun,TS.WBS_ID,WB.WBS_NAME 
from (select * from USER_TIMESHEET_NEW  WHERE user_id='".$user_id."'
                                  and  to_char(ts_date,'Mon-YYYY')='$month-$year' ) TS
 JOIN WBS WB ON TS.WBS_ID=WB.WBS_ID
 ORDER BY SUBMIT_DATE DESC
 ");
/*        $query = $this->db->query("
                                  SELECT *
                                  FROM
                                  (SELECT *
                                  FROM (
                                  (SELECT *
 FROM (
 SELECT ts_id,
 substr(
 ts_id,
 1,
 instr(
 ts_id,
 '.'
 ) - 1
 ) AS wp,
 substr(
 ts_id,
 instr(
 ts_id,
 '.'
 ) + 1
 ) AS date_id,
 e.wbs_id,
 c.rp_id,
 c.user_id,
 f.user_name,
 c.project_id,
 d.project_name,
 e.wbs_name,
 subject,
 message,
 hour_total,
 ts_date,
 TO_CHAR(
 ts_date,
 'mm'
 ) AS bulan,
 TO_CHAR(
 ts_date,
 'month'
 ) AS month,
 TO_CHAR(
 ts_date,
 'YYYY'
 ) AS tahun,
 longitude,
 latitude,
 submit_date,
is_approved
 FROM
(select wp_id,is_approved,submit_date,LATITUDE,LONGITUDE,TS_DATE,HOUR_TOTAL,MESSAGE,SUBJECT,TS_ID from timesheet union select wp_id,is_approved,submit_date,LATITUDE,LONGITUDE,TS_DATE,HOUR_TOTAL,MESSAGE,SUBJECT,TS_ID from temporary_timesheet) a
 LEFT JOIN (select wp_id,rp_id,wbs_id from wbs_pool union select wp_id,rp_id,wbs_id from temporary_wbs_pool) b ON a.wp_id = b.wp_id
 LEFT JOIN resource_pool c ON b.rp_id = c.rp_id
 LEFT JOIN projects d ON c.project_id = d.project_id
 LEFT JOIN (select wbs_id,wbs_name from wbs union select wbs_id,wbs_name from temporary_wbs) e ON b.wbs_id = e.wbs_id
 INNER JOIN users f ON c.user_id = f.user_id
 )
 ORDER BY SUBMIT_DATE DESC)
                                  )
                                  ORDER BY SUBMIT_DATE DESC)
                                  WHERE user_id='".$user_id."'
                                  and  to_char(ts_date,'Mon-YYYY')='$month-$year'");*/
        $hasil = $query->result_array();
        return $hasil;

    }
    function Timesheet_bydate($user_id,$date){
        //ada perubahan

        //echo "date : $date<br>";
        $query = $this->db->query("
          SELECT sum(HOUR_TOTAL) as HOUR_TOTAL FROM TIMESHEET 
JOIN WBS_POOL ON  WBS_POOL.WP_ID=TIMESHEET.WP_ID
JOIN RESOURCE_POOL ON  RESOURCE_POOL.RP_ID=WBS_POOL.RP_ID
where TS_DATE=to_date('$date','yyyy-mm-dd') AND RESOURCE_POOL.USER_ID = '".$user_id."' and TIMESHEET.IS_APPROVED = 1");
        $hasil = $query->row()->HOUR_TOTAL;
        //echo $this->db->last_query();
        return $hasil;

    }
    function selectTimesheet($user_id){
        //ada perubahan
        $now = date('Y-m-d');
        $past = date('Y-m-d', strtotime('-6 days'));
        $query = $this->db->query("
                                  SELECT DISTINCT ts_id,wp,date_id,wbs_id,rp_id,user_id,user_name,project_id,project_name,wbs_name,subject,message,hour_total,ts_date,bulan,month,tahun,longitude,latitude,submit_date,is_approved,task_member_rebaseline,task_rebaseline,timesheet_rebaseline
                                  FROM
                                  (SELECT *
                                  FROM USER_TIMESHEET_NEW
                                  ORDER BY SUBMIT_DATE DESC)
                                  WHERE user_id='".$user_id."'
                                  and ts_date between to_date('$past','yyyy-mm-dd') and to_date('$now','yyyy-mm-dd')");
        $hasil = $query->result_array();

        echo $this->db->last_query();
        die;
        return $hasil;

    }



    function selectTimesheetAll($user_id){
        return $this->db->query(" SELECT *
  FROM
  (SELECT *
  FROM USER_TIMESHEET
  ORDER BY SUBMIT_DATE DESC)
  WHERE user_id='".$user_id."'")->result();
    }


    function inputWeekly($data1,$data){
        $wp=$data['WP_ID'];
        $tgl=date_format(date_create($data1['TS_DATE']),'Ymd');
        $id=$wp.".".$tgl;
        $date=date("Y-m-d");
        $dateTS=$data1['TS_DATE'];
        $this->db->set('TS_ID',$id);
        $this->db->set('SUBJECT',$data1['SUBJECT']);
        $this->db->set('MESSAGE',$data1['MESSAGE']);
        $this->db->set('WP_ID',$wp);
        $this->db->set('HOUR_TOTAL',$data1['HOUR_TOTAL']);
        $this->db->set('TS_DATE',"to_date('$dateTS','YYYY-MM-DD')",false);
        $this->db->set('SUBMIT_DATE',"to_date('$date','YYYY-MM-DD')",false);

        //$data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");

        $this->db->insert("TIMESHEET");

        $this->updateProgress($wp);

    }



    function selectprojectid($id){
        $query = $this->db->query("SELECT * FROM V_PROJECT_TEAM_MEMBER WHERE USER_ID='".$id."'");
        $hasil = $query->result_array();
        return $hasil;
    }

    function getUser($id){
        $query = $this->db->query("SELECT * FROM USERS WHERE USER_ID='".$id."'");
        $hasil = $query->result_array();
        return $hasil;
    }

    function updateProgress($ts_id){
        $wp = $this->db->query("select wp_id from timesheet where ts_id = '$ts_id'")->row()->WP_ID;
        $wbs=$this->db->query("SELECT WBS_ID from WBS_POOL WHERE WP_ID='".$wp."'")->row()->WBS_ID;
        $work=$this->db->query("select sum(hour_total) as WORK_H, wbs_id from user_timesheet where wbs_id='$wbs'  group by wbs_id")->row()->WORK_H;
        $wc=$this->db->query("select work_complete from wbs where wbs_id='$wbs'")->row()->WORK_COMPLETE;
        if($work*100/$wc>100){
            $this->db->query("update wbs set work='$work', progress_wbs=100 where wbs_id='$wbs'");
        }else{
            $this->db->query("update wbs set work='$work', progress_wbs=$work*100/$wc where wbs_id='$wbs'");
        }
        $allParent=$this->getAllParentWBS($wbs);
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
            if($h*100/$wcAp>100){
                $this->db->query("update wbs set work='$h',progress_wbs=100 where wbs_id='$ap->WBS_ID'");
            }else{
                $this->db->query("update wbs set work='$h',progress_wbs=$h*100/$wcAp where wbs_id='$ap->WBS_ID'");
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
    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    function getAllChildWBS($wbs){
        return $this->db->query("SELECT CONNECT_BY_ISLEAF AS LEAF, WBS.*, LEVEL
        FROM WBS where WBS_ID NOT IN ('$wbs') and CONNECT_BY_ISLEAF=1  CONNECT BY  WBS_PARENT_ID= PRIOR WBS_ID
        START WITH WBS_ID='$wbs' ORDER SIBLINGS BY WBS_PARENT_ID ")->result();
    }

    function getAllParentWBS($wbs){
        return $this->db->query("SELECT CONNECT_BY_ISLEAF AS LEAF, WBS.*, LEVEL
        FROM WBS where WBS_ID NOT IN ('".$wbs."') CONNECT BY  WBS_ID=PRIOR WBS_PARENT_ID
        START WITH WBS_ID='".$wbs."' ORDER SIBLINGS BY WBS_PARENT_ID")->result();
    }
    function updateWeeklyTS($data1,$data){
        $wp=$data['WP_ID'];
        $tgl=date_format(date_create($data1['TS_DATE']),'Ymd');
        $id=$wp.".".$tgl;
        $date=date("Y-m-d");
        if(empty($data1['HOUR_TOTAL'])){
            $data = array(
                'MESSAGE' => $data1['MESSAGE'],
                'SUBJECT'  => $data1['SUBJECT'],
                'HOUR_TOTAL'  => $data1['HOUR_TOTAL']
            );
            $this->db->where('TS_ID', $id);
            $this->db->update('TIMESHEET', $data);
            $this->db->query("UPDATE TIMESHEET SET SUBMIT_DATE=to_date('".$date."','YYYY-MM-DD') WHERE TS_ID='".$id."'");

            $this->updateProgress($wp);
        }else{
            $data = array(
                'HOUR_TOTAL'  => $data1['HOUR_TOTAL']
            );
            $this->db->where('TS_ID', $id);
            $this->db->update('TIMESHEET', $data);
            $this->db->query("UPDATE TIMESHEET SET SUBMIT_DATE=to_date('".$date."','YYYY-MM-DD') WHERE TS_ID='".$id."'");

            $this->updateProgress($wp);
        }
    }
    function updateTimesheet($data,$id){
        $date =	$data['TS_DATE'] ;
        $tgl=date_format(date_create($data['TS_DATE']),'Ymd');
        $data = array(
            'MESSAGE' => $data['MESSAGE'],
            'SUBJECT'  => $data['SUBJECT'],
            'HOUR_TOTAL'  => $data['HOUR_TOTAL']
        );
        $this->db->where('TS_ID', $id);
        $this->db->update('TIMESHEET', $data);
        $wp=$this->db->query("select WP_ID from timesheet where ts_id='".$id."'")->row_array()->WP_ID;
        $this->updateProgress($wp);
    }

    function selectCalendar(){
        $sql="select HOLIDAY_ID, HOLIDAY, TO_CHAR(HOLIDAY_START,'yyyy-mm-dd') AS HOLIDAY_START,TO_CHAR(HOLIDAY_END,'yyyy-mm-dd') AS HOLIDAY_END, COLOR FROM P_HOLIDAY where HOLIDAY_START is not null";
        $query = $this->db->query($sql);
        $hasil = $query->result_array();
        return $hasil;
    }

    function selectHoliday(){
        $query = $this->db->get('P_HOLIDAY');
        $hasil = $query->result_array();
        return $hasil;

    }

    function selectTimesheet2($id_bu){
        $query = $this->db->query("
  SELECT *
  FROM (SELECT *
  FROM V_LAST_ACT ORDER BY ts_date DESC) WHERE  rownum <= 5 and bu_id='".$id_bu."' ");
        $hasil = $query->result_array();
        return $hasil;

    }


    function selectTotalHour($id,$dt0,$dt6,$wp){
        return $this->db->query("
  SELECT SUM(HOUR_TOTAL) as TOTAL_HOUR
  FROM user_timesheet
  WHERE user_id ='".$id."'
  AND wp ='".$wp."'
  AND ts_date
  BETWEEN to_date('".$dt0."','YYYY-MM-DD')
  AND to_date('".$dt6."','YYYY-MM-DD')")->row()->TOTAL_HOUR;
    }

    function selectHour($id,$dt0,$dt6,$wp){
        return $this->db->query("
  SELECT HOUR_TOTAL as HOUR,to_char(ts_date,'DAY',
  'NLS_DATE_LANGUAGE=''numeric date language''') as d
  FROM user_timesheet
  WHERE user_id ='".$id."'
  AND wp ='".$wp."'
  AND ts_date
  BETWEEN to_date('".$dt0."','YYYY-MM-DD')
  AND to_date('".$dt6."','YYYY-MM-DD')")->result_array();
    }




    function selectTotalHourProject($id,$dt0,$dt6,$user_id){
        return $this->db->query("
  SELECT SUM(HOUR_TOTAL) as TOTAL_HOUR
  FROM user_timesheet
  WHERE user_id ='".$user_id."'
  AND project_id ='".$id."'
  AND ts_date
  BETWEEN to_date('".$dt0."','YYYY-MM-DD')
  AND to_date('".$dt6."','YYYY-MM-DD')")->row()->TOTAL_HOUR;
    }

    function selectHourProject($id,$dt0,$dt6,$user_id){
        return $this->db->query("

  SELECT SUM(HOUR_TOTAL) as HOUR,to_char(ts_date,'DAY',
  'NLS_DATE_LANGUAGE=''numeric date language''') as d
  FROM user_timesheet
  WHERE user_id ='".$user_id."'
and  PROJECT_ID ='".$id."'
  AND ts_date
  BETWEEN to_date('".$dt0."','YYYY-MM-DD')
  AND to_date('".$dt6."','YYYY-MM-DD')
GROUP BY TS_DATE")->result_array();
    }

    function selectHourAllProject($dt0,$dt6,$user_id){
        return $this->db->query(" SELECT SUM(HOUR_TOTAL) as HOUR,ts_date
                                  FROM user_timesheet
                                  WHERE user_id ='".$user_id."'
                                  AND is_approved = 1
                                  AND ts_date
                                  BETWEEN to_date('".$dt0."','YYYY-MM-DD')
                                  AND to_date('".$dt6."','YYYY-MM-DD')
                                GROUP BY TS_DATE")->result_array();
    }

    function selectTotalHourAllProject($dt0,$dt6,$user_id){
        return $this->db->query("
  SELECT SUM(HOUR_TOTAL) as TOTAL_HOUR
  FROM user_timesheet
  WHERE user_id ='".$user_id."'
  AND ts_date
  BETWEEN to_date('".$dt0."','YYYY-MM-DD')
  AND to_date('".$dt6."','YYYY-MM-DD')
  and is_approved = 1")->row()->TOTAL_HOUR;
    }

    function inputTimesheet($data){

        $sql = "select u.user_name,pm.email,project_name,wbs_name from wbs_pool wp join wbs on wp.wbs_id=wbs.wbs_id
join projects p on p.project_id = wbs.project_id 
join users pm on p.pm_id = pm.user_id
join resource_pool rp on wp.rp_id=rp.rp_id
join users u on rp.user_id = u.user_id
where wp_id = $data[WP_ID]";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $info=$q->row_array();
        }
        $this->confirmationTimesheetEmailtoPM($data,$info);
        print_r($info);
        die;
        //change date input for readable to sql
        $tgl=date_format(date_create($data['DATE']),'Ymd');


        //check timesheet data for this date ,
        //0 = no data
        //-1 = have an old data (Only one data)
        //1 = have a new data
        $jumlahts=$this->checkTSData($data['WP_ID'],$tgl);
        //insert new data

        if($jumlahts == 0){
            $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

            //data for insert
            $TS_ID = $data['WP_ID'].".$tgl.".str_pad(($getCountTimesheet+1),2,"0",STR_PAD_LEFT);
            $SUBJECT = $data['SUBJECT'];
            $MESSAGE = $data['MESSAGE'];
            $HOUR_TOTAL = $data['WORK_HOUR'];
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];
            $SUBMITDATE = $data['SUBMIT_DATE'];

            $this->db->query("INSERT INTO TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE,SUBMIT_DATE) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE',to_timestamp('$SUBMITDATE','YYYY-MM-DD HH24:MI:SS'))");

        }
        //insert new data with add prefix number at primary key
        elseif($jumlahts == 1){
            //get timesheet total this day
            $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

            //data for insert
            $TS_ID = $data['WP_ID'].".$tgl.".str_pad(($getCountTimesheet+1),2,"0",STR_PAD_LEFT);
            $SUBJECT = $data['SUBJECT'];
            $MESSAGE = $data['MESSAGE'];
            $HOUR_TOTAL = $data['WORK_HOUR'];
            $SUBMITDATE = $data['SUBMIT_DATE'];
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];

            $this->db->query("INSERT INTO TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE,SUBMIT_DATE) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE',to_timestamp('$SUBMITDATE','YYYY-MM-DD HH24:MI:SS'))");


        }
        //change old primary key style first if data detected as old data
        elseif($jumlahts == -1){

            //update query
            $getOldData = $this->db->query("select * from timesheet where TS_DATE = to_date('$tgl','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array();
            $this->db->set('TS_ID',$getOldData[0]['TS_ID'].".".str_pad(1,2,"0",STR_PAD_LEFT));
            $this->db->where("TS_DATE = to_date('$tgl','yyyymmdd')");
            $this->db->like('TS_ID', $data['WP_ID'].'.','after');

            $queryupdate = "update TIMESHEET set TS_ID = '".$getOldData[0]['TS_ID'].".01' 
                              where TS_DATE = to_date('$tgl','yyyymmdd') 
                              and TS_ID LIKE '".$data['WP_ID'].".%'";
            $this->db->query($queryupdate);


            //insert query
            $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from TIMESHEET where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

            //data for insert
            $TS_ID = $data['WP_ID'].".$tgl.".str_pad(($getCountTimesheet+1),2,"0",STR_PAD_LEFT);
            $SUBJECT = $data['SUBJECT'];
            $MESSAGE = $data['MESSAGE'];
            $HOUR_TOTAL = $data['WORK_HOUR'];
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];

            $this->db->query("INSERT INTO TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE')");
        }

    }
    function editTimesheet($data){

        //change date input for readable to sql
        $tgl=date_format(date_create($data['DATE']),'Ymd');

        //check timesheet data for this date ,
        //0 = no data
        //-1 = have an old data (Only one data)
        //1 = have a new data

            //data for insert
            $TS_ID = $data['TS_ID'];
            $SUBJECT = $data['SUBJECT'];
            $MESSAGE = $data['MESSAGE'];
            $HOUR_TOTAL = $data['WORK_HOUR'];
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];
            $SUBMITDATE = $data['SUBMIT_DATE'];



         $this->db->query("update timesheet
                             set IS_APPROVED = -1,TS_ID = '$TS_ID' , SUBJECT='$SUBJECT', MESSAGE = '$MESSAGE', HOUR_TOTAL = '$HOUR_TOTAL', TS_DATE =$TS_DATE, WP_ID='$WP_ID', LATITUDE='$LATITUDE', LONGITUDE='$LONGITUDE',SUBMIT_DATE = to_timestamp('$SUBMITDATE','YYYY-MM-DD HH24:MI:SS') 
                           where TS_ID = '$TS_ID'");
    }

    function checkTSData($wp,$date){
        //for update old data from this date
        $old_data = $this->db->query("select max(substr(TS_ID,-3,3)) as TS_ID from TIMESHEET where TS_DATE = to_date('$date','yyyymmdd') and TS_ID LIKE '$wp.%'")->result_array();
        $data = explode('.',$old_data[0]['TS_ID']);
        //if no data from this date
        if($old_data[0]['TS_ID'] == null ){
            return 0;
        }
        //if have data but an old data
        elseif(count($data) == 1){
            return -1;
        }
        //if have data and a new data
        elseif(count($data) == 2){
            return 1;
        }

        /*
        die;
        $id=$wp.".".date_format(date_create($date),'Ymd');
        $query=$this->db->query("select HOUR_TOTAL as HOURS from TIMESHEET where TS_ID='".$id."'");

        if($query->num_rows() > 0){
            return $query->num_rows();
        }else{
            return 'a';
        }*/
    }

    function confirmTimesheet($timesheet_id,$approver,$confirm_code){
        $date = date('Y-m-d');
        $timesheetdata = $this->db->query("select subject,message,hour_total,ts_date,approval_date  from timesheet where ts_id = '$timesheet_id'")->row_array();
        $userdata = $this->db->query("select u.email,u.user_name from timesheet ts join wbs_pool wp on ts.wp_id = wp.wp_id
                                   join resource_pool rp  on rp.rp_id = wp.rp_id
                                   join users u on u.user_id = rp.user_id
                                   where ts.ts_id = '$timesheet_id'")->row();
        $query = "update timesheet 
                  set IS_APPROVED = $confirm_code, CONFIRMED_BY = '$approver' , APPROVAL_DATE = to_date('$date','yyyy-mm-dd')
                  where TS_ID = '$timesheet_id'";
        $exec = $this->db->query($query);

        if($this->db->affected_rows() == 1){
            $this->confirmationTimesheetEmail($userdata->EMAIL,$userdata->USER_NAME,$timesheetdata,$confirm_code);
            return true;
        }
        else{
            return false;
        }
    }

    function inputTimesheetTemp($data,$rh_id){

        //change date input for readable to sql
        $tgl=date_format(date_create($data['DATE']),'Ymd');

        //check timesheet data for this date ,
        //0 = no data
        //-1 = have an old data (Only one data)
        //1 = have a new data
        $jumlahts=$this->checkTSData($data['WP_ID'],$tgl);
        //insert new data

        if($jumlahts == 0){
            $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from 
                                                    ( select TS_ID,TS_DATE from timesheet union select TS_ID,TS_DATE from temporary_timesheet)
                                                    where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

            //data for insert
            $TS_ID = $data['WP_ID'].".$tgl.".str_pad(($getCountTimesheet+1),2,"0",STR_PAD_LEFT);
            $SUBJECT = $data['SUBJECT'];
            $MESSAGE = $data['MESSAGE'];
            $HOUR_TOTAL = $data['WORK_HOUR'];
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];
            $SUBMITDATE = $data['SUBMIT_DATE'];

            $this->db->query("INSERT INTO TEMPORARY_TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE,IS_VALID,SUBMIT_DATE,ACTION,RH_ID) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE',1,to_timestamp('$SUBMITDATE','YYYY-MM-DD HH24:MI:SS'),'create','$rh_id')");


        }
        //insert new data with add prefix number at primary key
        elseif($jumlahts == 1){
            //get timesheet total this day
            $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from (
                                                      select TS_ID,TS_DATE from timesheet union select TS_ID,TS_DATE from temporary_timesheet
                                                    ) where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

            //data for insert
            $TS_ID = $data['WP_ID'].".$tgl.".str_pad(($getCountTimesheet+1),2,"0",STR_PAD_LEFT);
            $SUBJECT = $data['SUBJECT'];
            $MESSAGE = $data['MESSAGE'];
            $HOUR_TOTAL = $data['WORK_HOUR'];
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];
            $SUBMITDATE = $data['SUBMIT_DATE'];


            $this->db->query("INSERT INTO TEMPORARY_TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE,IS_VALID,SUBMIT_DATE,ACTION,RH_ID) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE',1,to_timestamp('$SUBMITDATE','YYYY-MM-DD HH24:MI:SS'),'create','$rh_id')");


        }
        //change old primary key style first if data detected as old data
        elseif($jumlahts == -1){

            //update query
            $getOldData = $this->db->query("select * from timesheet where TS_DATE = to_date('$tgl','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array();
            $this->db->set('TS_ID',$getOldData[0]['TS_ID'].".".str_pad(1,2,"0",STR_PAD_LEFT));
            $this->db->where("TS_DATE = to_date('$tgl','yyyymmdd')");
            $this->db->like('TS_ID', $data['WP_ID'].'.','after');

            $queryupdate = "update TIMESHEET set TS_ID = '".$getOldData[0]['TS_ID'].".01' 
                              where TS_DATE = to_date('$tgl','yyyymmdd') 
                              and TS_ID LIKE '".$data['WP_ID'].".%'";
            $this->db->query($queryupdate);


            //insert query
            $getCountTimesheet = ($this->db->query("select max(substr(TS_ID,-2,2)) as TS_ID from (
                                                      select TS_ID,TS_DATE from timesheet union select TS_ID,TS_DATE from temporary_timesheet
                                                    ) where TS_DATE = to_date('".$tgl."','yyyymmdd') and TS_ID LIKE '".$data['WP_ID'].".%'")->result_array())[0]['TS_ID'];

            //data for insert
            $TS_ID = $data['WP_ID'].".$tgl.".str_pad(($getCountTimesheet+1),2,"0",STR_PAD_LEFT);
            $SUBJECT = $data['SUBJECT'];
            $MESSAGE = $data['MESSAGE'];
            $HOUR_TOTAL = $data['WORK_HOUR'];
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];

            $this->db->query("INSERT INTO TEMPORARY_TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE,IS_VALID,ACTION,RH_ID)) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE',1,'create','$rh_id')");
        }
    }
    function editTimesheetTemp($data,$rh_id){

        //change date input for readable to sql
        $tgl=date_format(date_create($data['DATE']),'Ymd');

        //check timesheet data for this date ,
        //0 = no data
        //-1 = have an old data (Only one data)
        //1 = have a new data

            //data for insert
            $TS_ID = $data['TS_ID'];
            $SUBJECT = $data['SUBJECT'];
            $MESSAGE = $data['MESSAGE'];
            $HOUR_TOTAL = $data['WORK_HOUR'];
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];
            $SUBMITDATE = $data['SUBMIT_DATE'];

            $this->db->query("UPDATE TEMPORARY_TIMESHEET 
                             set IS_APPROVED = -1,TS_ID = '$TS_ID' , SUBJECT='$SUBJECT', MESSAGE = '$MESSAGE', HOUR_TOTAL = '$HOUR_TOTAL', TS_DATE =$TS_DATE, WP_ID='$WP_ID', LATITUDE='$LATITUDE', LONGITUDE='$LONGITUDE',SUBMIT_DATE = to_timestamp('$SUBMITDATE','YYYY-MM-DD HH24:MI:SS') 
                           where TS_ID = '$TS_ID'");

    }

    function confirmTimesheetTemp($timesheet_id,$approver,$confirm_code){
        $date = date('Y-m-d');
        $query = "update temporary_timesheet 
                  set IS_APPROVED = $confirm_code, CONFIRMED_BY = '$approver' , APPROVAL_DATE = to_date('$date','yyyy-mm-dd')
                  where TS_ID = '$timesheet_id'";
        $exec = $this->db->query($query);
        $timesheetdata = $this->db->query("select subject,message,hour_total,ts_date,approval_date  from timesheet where ts_id = '$timesheet_id'")->row_array();
        $userdata = $this->db->query("select u.email,u.user_name from timesheet ts join wbs_pool wp on ts.wp_id = wp.wp_id
                                   join resource_pool rp  on rp.rp_id = wp.rp_id
                                   join users u on u.user_id = rp.user_id
                                   where ts.ts_id = '$timesheet_id'")->row();
        if($this->db->affected_rows() == 1){
            $this->confirmationTimesheetEmail($userdata->EMAIL,$userdata->USER_NAME,$timesheetdata,$confirm_code);
            return 'success';
        }
        else{
            return 'failed';
        }
    }

    function confirmationTimesheetEmail($email,$name,$timesheetdata,$approval_code){
        //subject,message,hour_total,ts_date,approval_date
        extract($timesheetdata);
        $confirmstring = ($approval_code == 1 ? "DITERIMA" : "DITOLAK");
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
        $this->email->to($email);
      //  $this->email->to('salma.ulhaq@sigma.co.id');
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject('User Timesheet Approval');
        $this->email->message("<!DOCTYPE html>
      <html>
      <head>
      <meta name='viewport' content='width=device-width' />
      <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
      <title>Account Activation</title>

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
      table.footer-wrap { width: 100%;	clear:both!important;
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
      <img src='cid:".$cid_logo."' height='173' width='581' alt='logo Telkomsigma' />
      <h2>Hi ".$name.",</h3>
      <br/>
      <h4>Timesheet yang telah anda input :</h4>
      <h4>Tanggal : $TS_DATE</h4>
      <h4>Jumlah Jam : $HOUR_TOTAL</h4>
      <h4>Subject : $SUBJECT</h4>
      <h3>Telah <b style='color:white;background-color: red'>$confirmstring</b> pada tanggal $APPROVAL_DATE </h3>
      <br>
      <br/>
      <p style='text-align: left'>Trouble with timesheet ? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
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
            return true;
        }else{
            return false;
        }

    }
    function confirmationTimesheetEmailtoPM($data,$info){
        $tgl=date_format(date_create($data['DATE']),'Ymd');

        $SUBJECT = $data['SUBJECT'];
        $MESSAGE = $data['MESSAGE'];
        $HOUR_TOTAL = $data['WORK_HOUR'];
        $TS_DATE = "to_date('$tgl','yyyymmdd')";
        $WP_ID = $data['WP_ID'];
        $LATITUDE = $data['LATITUDE'];
        $LONGITUDE = $data['LONGITUDE'];
        $SUBMITDATE = $data['SUBMIT_DATE'];
        //subject,message,hour_total,ts_date,approval_date
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
        $this->email->to("dyaskur@gmail.com");
      //  $this->email->to('salma.ulhaq@sigma.co.id');
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject('User Timesheet need Approval');
        $this->email->message("<!DOCTYPE html>
      <html>
      <head>
      <meta name='viewport' content='width=device-width' />
      <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
      <title>Account Activation</title>

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
      table.footer-wrap { width: 100%;	clear:both!important;
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
      <img src='cid:".$cid_logo."' height='173' width='581' alt='logo Telkomsigma' />
      <h2>Hi ".$info["pm_name"].",</h3>
      <br/>
      <h4>User $info[user_name] telah mengisi timesheet sebagai berikut :</h4>
      <h4>Project : $info[project_name]</h4>
      <h4>WBS : $info[wbs_name]</h4>
      <h4>Tanggal : $TS_DATE</h4>
      <h4>Jumlah Jam : $HOUR_TOTAL</h4>
      <h4>Subject : $SUBJECT</h4>
      <br>
      <br/>
      <p style='text-align: left'>Trouble with timesheet ? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
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
            return true;
        }else{
            return false;
        }

    }

}


