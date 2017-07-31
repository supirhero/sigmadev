<?php

Class M_timesheet extends CI_Model{

    /*  function editMenu($data){
    $this->db->where('id', $id);
    $this->db->update('mytable', $data);
  }*/

    function selectTimesheet_bydate($user_id,$date){
        //ada perubahan
        $query = $this->db->query("
  SELECT *
  FROM
  (SELECT *
  FROM USER_TIMESHEET where ts_date='$date'
  ORDER BY ts_date DESC)
  WHERE user_id='".$user_id."'");
        $hasil = $query->result_array();
        return $hasil;

    }
    function selectTimesheet($user_id){
        //ada perubahan
        $query = $this->db->query("
  SELECT *
  FROM
  (SELECT *
  FROM USER_TIMESHEET
  ORDER BY ts_date DESC)
  WHERE user_id='".$user_id."' and rownum < 20");
        $hasil = $query->result_array();
        return $hasil;

    }



    function selectTimesheetAll($user_id){
        return $this->db->query(" SELECT *
  FROM
  (SELECT *
  FROM USER_TIMESHEET
  ORDER BY ts_date DESC)
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

    function updateProgress($wp){
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
        return $this->db->query("

  SELECT SUM(HOUR_TOTAL) as HOUR,to_char(ts_date,'DAY',
  'NLS_DATE_LANGUAGE=''numeric date language''') as d
  FROM user_timesheet
  WHERE user_id ='".$user_id."'

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
  AND to_date('".$dt6."','YYYY-MM-DD')")->row()->TOTAL_HOUR;
    }

    public function insertTimesheet($data){

        $date =	$data['TS_DATE'] ;
        //$date2 =	$data2['TS_DATE'] ;
        $tgl=date_format(date_create($data['TS_DATE']),'Ymd');
        $this->db->set('TS_ID',$data['WP_ID'].".".$tgl);
        $this->db->set('SUBJECT',$data['SUBJECT']);
        $this->db->set('MESSAGE',$data['MESSAGE']);
        $this->db->set('WP_ID',$data['WP_ID']);
        $this->db->set('HOUR_TOTAL',$data['HOUR_TOTAL']);
        $this->db->set('TS_DATE',"to_date('$date','YYYY-MM-DD')",false);
        $this->db->set('SUBMIT_DATE',"to_date('$date','YYYY-MM-DD')",false);

        //$data['PROJECT_ID'] 		= $this->input->post("PROJECT_ID");
        $wp=$data['WP_ID'];
        $this->db->insert("TIMESHEET");
        $this->updateProgress($data['WP_ID']);
    }

    function inputTimesheet($data){

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

            $this->db->query("INSERT INTO TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE')");


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
            $TS_DATE = "to_date('$tgl','yyyymmdd')";
            $WP_ID = $data['WP_ID'];
            $LATITUDE = $data['LATITUDE'];
            $LONGITUDE = $data['LONGITUDE'];

            $this->db->query("INSERT INTO TIMESHEET 
                              (TS_ID, SUBJECT, MESSAGE, HOUR_TOTAL, TS_DATE, WP_ID, LATITUDE, LONGITUDE) 
                              VALUES
                              ('$TS_ID','$SUBJECT','$MESSAGE','$HOUR_TOTAL',$TS_DATE,'$WP_ID','$LATITUDE','$LONGITUDE')");

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
        die;
        $id=$data['WP_ID'].".".$tgl;
        $date=date("Y-m-d");
    }

    function checkTSData($wp,$date){
        //for update old data from this date
        $old_data = $this->db->query("select max(substr(TS_ID,-3,3)) as TS_ID from TIMESHEET where TS_DATE = to_date('$date','yyyymmdd') and TS_ID LIKE '$wp.%'")->result_array();
        $data = explode('.',$old_data[0]['TS_ID']);
        //if no data from this date
        if($old_data[0]['TS_ID'] == null ){
            //echo "No data";
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

}


