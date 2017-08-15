<?php

Class M_home extends CI_Model{

    function selectBU($hasil){
        $bu = $this->session->userdata('BU_ID');
        $query = $this->db->query("select BU_NAME FROM P_BU WHERE BU_ID=''".$bu.'');

    }

    function assignmentView($user_id){
        $query = $this->db->query(" SELECT a.USER_ID, c.USER_NAME, f.bu_name ,a.RP_ID, a.PROJECT_ID, b.PROJECT_NAME, d.WP_ID, d.WBS_ID, e.WBS_NAME, e.START_DATE, e.FINISH_DATE
                                    FROM RESOURCE_POOL a LEFT JOIN PROJECTS b
                                    ON a.PROJECT_ID=b.PROJECT_ID LEFT JOIN USERS c
                                    ON a.USER_ID=c.USER_ID LEFT JOIN WBS_POOL d
                                    ON a.RP_ID=d.RP_ID LEFT JOIN WBS e
                                    ON d.WBS_ID=e.WBS_ID join p_bu f
                                    on b.bu_code = f.bu_code
                                    where a.user_id='".$user_id."'");
        $hasil = $query->result_array();

        //reserve variable
        $array_fix = [];
        $bu_temp_sort = [];
        $project_temp_sort = [];
        $bu_temp = [];
        $project_temp = [];

        //get bu name and project name
        foreach ($hasil as $data){
            array_push($bu_temp,$data['BU_NAME']);
            array_push($project_temp,$data['PROJECT_NAME']);
        }

        //filter bu nane and project name with unique
        $bu_temp = array_unique($bu_temp);
        $project_temp = array_unique($project_temp);

        //sorting bu_name and project array
        foreach ($bu_temp as $data){
            array_push($bu_temp_sort,$data);
        }
        foreach ($project_temp as $data){
            array_push($project_temp_sort,$data);
        }


        //merging assignment with same project
        $project_assignment_merge = [];
        foreach ($project_temp_sort as $data){
            $penampung = [];
            for($i = 0; $i < count($hasil) ; $i++){
                if($data == $hasil[$i]['PROJECT_NAME']){
                    if(count($penampung) == 0){
                        $penampung['project_name']=$data;
                        $penampung['bu_name'] = $hasil[$i]['BU_NAME'];
                        $penampung['assignment_list'] = [$hasil[$i]];
                    }
                    else{
                        array_push($penampung['assignment_list'],$hasil[$i]);
                    }
                }
            }
            array_push($project_assignment_merge,$penampung);

        }

        //merging $project_assignment_merge with same bu
        foreach ($bu_temp_sort as $data){
            $penampung = [];
            foreach ($project_assignment_merge as $data2){
                if($data == $data2['bu_name']){
                    if(count($penampung) == 0){
                        $penampung['bu_name'] = $data;
                        $penampung['project_detail'] = [];
                        array_push($penampung['project_detail'],$data2);
                    }
                    else{
                        array_push($penampung['project_detail'],$data2);
                    }
                }
            }
            array_push($array_fix,$penampung);
        }


        return $array_fix;
    }
    function assignmentProject($id){
        return $this->db->query("select * from v_project_team_member where user_id='". $id ."'")->result_array();
    }

    function getTotalHour($user_id,$bulan,$tahun)
    {
        return $this->db->query("SELECT
      SUM(HOUR_TOTAL) AS JML_JAM_BULANAN, TO_CHAR (TS_DATE, 'mm')
      FROM
      user_timesheet b
      WHERE
      user_id = '".$user_id."'
      AND TO_CHAR (TS_DATE, 'mm') = '".$bulan."'
      AND IS_APPROVED = 1
      AND TO_CHAR (TS_DATE, 'yyyy') = '".$tahun."'
      GROUP BY user_id,TO_CHAR (TS_DATE, 'mm')
      UNION ALL
      SELECT 0,'".$bulan."'
      FROM dual
      WHERE NOT EXISTS (SELECT SUM(HOUR_TOTAL) AS JML_JAM_BULANAN, TO_CHAR (TS_DATE, 'mm')
      FROM user_timesheet b
      WHERE
      user_id = '".$user_id."'
      AND TO_CHAR (TS_DATE, 'mm') = '".$bulan."'
      AND TO_CHAR (TS_DATE, 'yyyy') = '".$tahun."'
      GROUP BY user_id,TO_CHAR (TS_DATE, 'mm'))
      ")->row()->JML_JAM_BULANAN;
    }
    function getEntry($user_id,$bulan,$tahun)
    {
        return $this->db->query("SELECT
        COUNT (DISTINCT ts_date) AS jml_entry_bulanan,
        TO_CHAR (TS_DATE, 'mm') as BULAN,
        user_id
        FROM user_timesheet
        WHERE
        user_id = '".$user_id."'
        AND TO_CHAR (TS_DATE, 'mm') = '".$bulan."'
        AND IS_APPROVED = 1
        AND TO_CHAR (TS_DATE, 'yyyy') = '".$tahun."'
        AND (HOUR_TOTAL is not null and HOUR_TOTAL!='0')
        GROUP BY
        user_id, TO_CHAR (TS_DATE, 'mm')
        UNION ALL
        SELECT 0,'".$bulan."','".$user_id."'
        FROM dual
        WHERE NOT EXISTS (SELECT
        COUNT (DISTINCT ts_date) AS jml_entry_bulanan,
        TO_CHAR (TS_DATE, 'mm') as BULAN,
        user_id
        FROM user_timesheet
        WHERE
        user_id = '".$user_id."'
        AND TO_CHAR (TS_DATE, 'mm') = '".$bulan."'
        AND TO_CHAR (TS_DATE, 'yyyy') = '".$tahun."'
        AND (HOUR_TOTAL is not null and HOUR_TOTAL!='0')
        GROUP BY
        user_id, TO_CHAR (TS_DATE, 'mm'))")->row()->JML_ENTRY_BULANAN;
    }

    function getAllEntry($user_id,$tahun)
    {
        return
            $this->db->query("SELECT DISTINCT
          m.MONTH_DISPLAY,
          m.MONTH_VALUE,
          coalesce(ts.JML_ENTRY_BULANAN, 0) as JML_ENTRY_BULANAN,
          coalesce(ts.USER_ID, '".$user_id."') as user_id
          FROM
          (SELECT
          COUNT(DISTINCT ts_date) as JML_ENTRY_BULANAN,
          TO_CHAR (TS_DATE, 'mm') as BULAN,
          user_id
          FROM
          user_timesheet JOIN
          WORK_DAY m
          ON m.wd_id = TO_CHAR (TS_DATE, 'mm')
          WHERE
          user_id = '".$user_id."'
          AND TO_CHAR (TS_DATE, 'yyyy') = '".$tahun."'
          AND (HOUR_TOTAL is not null and HOUR_TOTAL!='0')
          GROUP BY
          user_id, TO_CHAR (TS_DATE, 'mm')) ts
          RIGHT outer JOIN WWV_FLOW_MONTHS_MONTH m ON ts.BULAN = m.MONTH_VALUE
          ORDER BY MONTH_VALUE")->result_array();
    }

    function getAllHour($user_id,$tahun)
    {
        return
            $this->db->query("SELECT DISTINCT
            m.MONTH_DISPLAY,
            m.MONTH_VALUE,
            coalesce(ts.JML_JAM_BULANAN, 0) as JML_JAM_BULANAN,
            coalesce(ts.USER_ID, '".$user_id."') as user_id
            FROM
            (SELECT
            SUM(HOUR_TOTAL) AS JML_JAM_BULANAN, TO_CHAR (TS_DATE, 'mm')  as BULAN,USER_ID
            FROM
            user_timesheet b
            WHERE
            user_id = '".$user_id."'
            AND TO_CHAR (TS_DATE, 'yyyy') = '".$tahun."'
            GROUP BY user_id,TO_CHAR (TS_DATE, 'mm')) ts
            RIGHT outer JOIN WWV_FLOW_MONTHS_MONTH m ON ts.BULAN = m.MONTH_VALUE
            ORDER BY MONTH_VALUE")->result_array();
    }

    function getProjects($user){
        return $this->db->query("select rp.*, pr.project_name
            from resource_pool rp left join projects pr on rp.project_id=pr.project_id where user_id='$user'")->result();
    }

    function p_teammember($idproject){
        $returndata = $this->db->query("SELECT users.user_id,users.user_name,users.email,profile.prof_name FROM RESOURCE_POOL
                      join USERS on RESOURCE_POOL.USER_ID=USERS.USER_ID
                      join PROFILE ON PROFILE.PROF_ID=USERS.PROF_ID
                      WHERE PROJECT_ID= ".$idproject)->result_array();
        for($i = 0; $i < count($returndata); $i++){
            $posisi = $this->db->query("select position from resource_pool 
                                        where project_id = $idproject and user_id = '".$returndata[$i]['USER_ID']."'")->row();
            $returndata[$i]['position'] = $posisi->POSITION;
        }
        return $returndata;
    }

    function projectissuelist($id){
        $query= $this->db->query("select a.ISSUE_ID,
        a.user_id,
        c.user_name as reported_by,
        a.PROJECT_ID,
        b.project_name,
        a.note,
        a.DATE_ISSUE,
        a.EVIDENCE,
        a.STATUS,
        a.PRIORITY,
        a.DURATION,
        a.SUBJECT
        from MANAGE_ISSUE a JOIN projects b
        on a.PROJECT_ID=b.PROJECT_ID join USERS c
        on a.user_id=c.user_id where b.PROJECT_ID='".$id."'");

        $returndata = $query->result_array();
        return $returndata;
    }

}
