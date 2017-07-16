<?php

Class M_report extends CI_Model{


function get_user_bu($buid){
    $query = $this->db->query("select * from users where bu_id in (SELECT p_bu.bu_id
FROM p_bu  CONNECT BY  bu_PARENT_ID= PRIOR bu_ID
START WITH bu_ID='$buid') order by bu_id");
 $hasil = $query->result_array();
 return $hasil;
 //return $this->db->query("SELECT * FROM V_CALCULATE_TASK_PER_USER ")->result();
 //return $id;
 //return "SELECT * FROM V_CALCULATE_TASK_PER_USER WHERE wbs_id='".$id."'";
 }



function get_user_on_bu($buid,$usr){
    $query = $this->db->query("SELECT * FROM USERS WHERE BU_ID='".$buid."' And USER_ID='".$usr."' ");
 $hasil = $query->result_array();
 return $hasil;
 //return $this->db->query("SELECT * FROM V_CALCULATE_TASK_PER_USER ")->result();
 //return $id;
 //return "SELECT * FROM V_CALCULATE_TASK_PER_USER WHERE wbs_id='".$id."'";
 }


function get_utilization_on_bu($buid){
    $query = $this->db->query("SELECT user_name,user_id,email FROM USERS WHERE BU_ID='".$buid."'");
 $hasil = $query->result_array();
 return $hasil;
 //return $this->db->query("SELECT * FROM V_CALCULATE_TASK_PER_USER ")->result();
 //return $id;
 //return "SELECT * FROM V_CALCULATE_TASK_PER_USER WHERE wbs_id='".$id."'";
 }



function getTotalHour ($user_id,$bulan,$tahun){
  return $this->db->query("SELECT
    SUM(HOUR_TOTAL) AS JML_JAM_BULANAN, TO_CHAR (TS_DATE, 'mm')
    FROM
    user_timesheet b
    WHERE
    user_id = '".$user_id."'
    AND TO_CHAR (TS_DATE, 'mm') = '".$bulan."'
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
        AND TO_CHAR (TS_DATE, 'yyyy') = '".$tahun."'
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
        GROUP BY
        user_id, TO_CHAR (TS_DATE, 'mm'))")->row()->JML_ENTRY_BULANAN;
      }
      function getCountUser($bu){
        return $this->db->query("select count(user_id) as COUNT_USER from users where bu_id in (SELECT p_bu.bu_id
FROM p_bu  CONNECT BY  bu_PARENT_ID= PRIOR bu_ID
START WITH bu_ID='$bu') and user_type_id='int' and is_active=1 order by bu_id
")->row()->COUNT_USER;
      }
     function getUtilBUYearly($bu,$tahun){
       return $this->db->query("Select sum( JML_JAM_BULANAN) as
       JML_JAM from (SELECT
          SUM(HOUR_TOTAL) AS JML_JAM_BULANAN,
          P_BU.BU_ID ,TO_CHAR (TS_DATE, 'mm') as bulan
          FROM
  USER_TIMESHEET
  INNER JOIN USERS ON USERS.USER_ID = USER_TIMESHEET.USER_ID
    INNER JOIN P_BU ON P_BU.BU_ID = USERS.BU_ID
      WHERE
  P_BU.BU_ID in (SELECT p_bu.bu_id
FROM p_bu  CONNECT BY  bu_PARENT_ID= PRIOR bu_ID
START WITH bu_ID='$bu')
and users.user_type_id='int'
and users.is_Active=1
  AND TO_CHAR (TS_DATE, 'yyyy') = '$tahun'
  GROUP BY
  P_BU.BU_ID,
  TO_CHAR (TS_DATE, 'mm')) a
   UNION ALL
        SELECT 0
        FROM dual
        WHERE NOT EXISTS (
        Select sum( JML_JAM_BULANAN) as
       JML_JAM from (SELECT
          SUM(HOUR_TOTAL) AS JML_JAM_BULANAN,
          P_BU.BU_ID ,TO_CHAR (TS_DATE, 'mm') as bulan
          FROM
  USER_TIMESHEET
    INNER JOIN USERS ON USERS.USER_ID = USER_TIMESHEET.USER_ID
        INNER JOIN P_BU ON P_BU.BU_ID = USERS.BU_ID
            WHERE
    P_BU.BU_ID in (SELECT p_bu.bu_id
FROM p_bu  CONNECT BY  bu_PARENT_ID= PRIOR bu_ID
START WITH bu_ID='$bu')
and users.user_type_id='int'
and users.is_Active=1
    AND TO_CHAR (TS_DATE, 'yyyy') = '$tahun'
    GROUP BY
  P_BU.BU_ID,
  TO_CHAR (TS_DATE, 'mm')) a) " )->row()->JML_JAM;
     }


     function getEntryBUYearly($bu,$tahun){
       return $this->db->query("Select
             sum( JML_ENTRY_BULANAN) as JML_ENTRY
         from
             (SELECT
                 COUNT(DISTINCT ts_date) AS jml_entry_bulanan,
                  TO_CHAR (TS_DATE, 'mm') AS BULAN,
                  P_BU.BU_ID
            FROM
                USER_TIMESHEET
            INNER
                JOIN USERS
            ON
                USERS.USER_ID = USER_TIMESHEET.USER_ID
            INNER JOIN
                P_BU
            ON
                P_BU.BU_ID = USERS.BU_ID
            WHERE
                P_BU.BU_ID = '$bu'
                and USERS.user_type_id='int'
                and users.is_active=1
            AND
                TO_CHAR (TS_DATE, 'yyyy') = '$tahun'
               GROUP BY
                  users.user_id,
                  P_BU.BU_ID,
                  TO_CHAR (TS_DATE, 'mm')
              )
              a group by  BU_ID
               UNION ALL
               SELECT 0
        FROM dual
        WHERE NOT EXISTS (
        Select
             sum( JML_ENTRY_BULANAN) as JML_ENTRY
         from
             (SELECT
                 COUNT(DISTINCT ts_date) AS jml_entry_bulanan,
                  TO_CHAR (TS_DATE, 'mm') AS BULAN,
                  P_BU.BU_ID
            FROM
                USER_TIMESHEET
            INNER
                JOIN USERS
            ON
                USERS.USER_ID = USER_TIMESHEET.USER_ID
            INNER JOIN
                P_BU
            ON
                P_BU.BU_ID = USERS.BU_ID
            WHERE
            P_BU.BU_ID = '$bu'
            and USERS.user_type_id='int'
            and users.is_active=1
            AND
                TO_CHAR (TS_DATE, 'yyyy') = '$tahun'
               GROUP BY
               users.user_id,
                  P_BU.BU_ID,
                  TO_CHAR (TS_DATE, 'mm')
              )
              a group by  BU_ID)
              "
              )->row()->JML_ENTRY;
     }


     function gettahunanbu($bu,$tahun)
    {
        $query = $this->db->query(
       "Select
        sum(coalesce( JML_ENTRY_BULANAN, 0)) as JML_ENTRY_BULANAN,
        MONTH_VALUE as BULAN
       from
        (SELECT
          COUNT(DISTINCT ts_date) AS jml_entry_bulanan,
          TO_CHAR (TS_DATE, 'mm') AS BULAN,
          P_BU.BU_ID
      FROM
        USER_TIMESHEET
      INNER
        JOIN USERS
      ON
        USERS.USER_ID = USER_TIMESHEET.USER_ID
      INNER JOIN
        P_BU
      ON
        P_BU.BU_ID = USERS.BU_ID
      WHERE
      P_BU.BU_ID = '$bu'
        and users.is_Active=1
        and users.user_type_id='int'
      AND
        TO_CHAR (TS_DATE, 'yyyy') = '$tahun'
          GROUP BY
          users.user_id,
          P_BU.BU_ID,
          TO_CHAR (TS_DATE, 'mm')
        )
        a
      RIGHT OUTER JOIN
        WWV_FLOW_MONTHS_MONTH
      on
        a.bulan=WWV_FLOW_MONTHS_MONTH.MONTH_VALUE
        group by month_value
    ORDER BY BULAN");
     $hasil = $query->result_array();
 return $hasil;

      }

 function getAllHourBU($bu,$tahun){
 $query = $this->db->query(
       "  Select coalesce( JML_JAM_BULANAN, 0) as JML_ENTRY_BULANAN,
       MONTH_VALUE as BULAN,coalesce( BU_ID, $bu) as
       BU_ID from (SELECT
          SUM(HOUR_TOTAL) AS JML_JAM_BULANAN,
          P_BU.BU_ID ,TO_CHAR (TS_DATE, 'mm') as bulan
          FROM
USER_TIMESHEET
INNER JOIN USERS ON USERS.USER_ID
= USER_TIMESHEET.USER_ID
INNER JOIN P_BU ON P_BU.BU_ID = USERS.BU_ID
WHERE
P_BU.BU_ID = '".$bu."'
and users.user_type_id='int'
and users.is_Active=1
AND TO_CHAR (TS_DATE, 'yyyy') = '".$tahun."'
GROUP BY
  P_BU.BU_ID,
  TO_CHAR (TS_DATE, 'mm')) a RIGHT OUTER JOIN
  WWV_FLOW_MONTHS_MONTH on a.bulan=WWV_FLOW_MONTHS_MONTH.MONTH_VALUE
ORDER BY BULAN");
     $hasil = $query->result_array();
 return $hasil;
 }

/*
function dashboard_all(){
  $sql="select b.bu_name,b.bu_code, b.bu_alias,b.bu_id, round(sum(ev),2) as EV, round(sum(pv)),2) as PV, round(sum(AC)),2) as AC, case when round(sum(ev)/sum(pv),2)<1 and round(sum(ev)/sum(pv),2) not in (0) then '0'||round(sum(ev)/sum(pv),2) else to_char(round(sum(ev)/sum(pv),2)) end as SPI, case when round(sum(ev)/sum(ac),2)<1 and round(sum(ev)/sum(ac),2) not in (0) then '0'||round(sum(ev)/sum(ac),2) else to_char(round(sum(ev)/sum(ac),2)) end as CPI
    from
    (select ev, pv, case when ev=0 then 0 else ac as ac,
                case when project_status not in ('In Progress') or type_of_effort not in (1,2) or type_of_effort not in (1,2) then '-'  when a.PROJECT_TYPE_ID like'Non%' then '-'  when (PV is null or PV=0) then '-' else case when round((EV/PV),2) <1 then '0'|| to_char(round((EV/PV),2)) else to_char(round((EV/PV),2)) end end as SPI,case when project_status not in ('In Progress') or type_of_effort not in (1,2) then '-'  when a.PROJECT_TYPE_ID like'Non%' then '-'  when (AC is null or AC=0)  then '-' when round((EV/AC),2)>1 then to_char(1) else case when round((EV/AC),2)  <1 then '0'|| to_char(round((EV/AC),2)) else to_char(round((EV/AC),2)) end end as CPI, a.project_id
    from tb_ev_project a
    left join tb_pv_project b
    on a.project_id=b.project_id
    left join tb_ac_project c on
    a.project_id=c.project_id) a inner join
    projects c on c.project_id=a.project_id
    inner join p_bu b on (b.bu_code=c.bu_code OR b.bu_alias=c.bu_code)
    group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id";
    $query = $this->db->query($sql);
    $hasil = $query->result_array();
    return $hasil;
  }
*/
    function dashboard_all(){
                $sql="select b.bu_name,b.bu_code, b.bu_alias,b.bu_id, round(sum(ev)/count(c.project_id),2) as EV, round(sum(pv)/count(c.project_id),2) as PV,
                round(sum(AC)/count(c.project_id),2) as AC,
                case when round((sum(ev)/count(c.project_id))/(sum(pv)/count(c.project_id)),2) between 0 and 1 then '0'||round((sum(ev)/count(c.project_id))/(sum(pv)/count(c.project_id)),2) else to_char(round((sum(ev)/count(c.project_id))/(sum(pv)/count(c.project_id)),2)) end as SPI,
                round((sum(ev)/count(c.project_id))/case when sum(ac)/count(c.project_id)=0 then 1 else sum(ac)/count(c.project_id) end,2) as CPI
                from
                (select ev, pv, case when pv=0 then 0 else round(ev/pv,2) end as spi,case when ev=0 then 0 else ac end as ac,case when ac=0 then 1 else round(ev/ac,2) end as cpi, a.project_id
                from tb_ev_project a
                left join tb_pv_project b
                on a.project_id=b.project_id
                left join tb_ac_project c on
                a.project_id=c.project_id) a inner join
                projects c on c.project_id=a.project_id
                inner join p_bu b on (b.bu_code=c.bu_code OR b.bu_alias=c.bu_code)
                where project_status='In Progress'
                and type_of_effort in (1,2)
                group by b.bu_code, b.bu_alias, b.bu_name, b.bu_id
                order by b.bu_name";
                $query = $this->db->query($sql);
                $hasil = $query->result_array();
                return $hasil;
              }


    function Portofolio_Total_Project($bu,$tahun){
        $returndata = $this->db->query("select count(a.project_id) as jml_project, bu_id from projects a right join p_bu b on (a.bu_code=b.bu_code or a.bu_code=b.bu_alias)  where bu_id='$bu' and to_char(date_created,'YYYY')='$tahun' group by bu_id")->row();
        if(isset($returndata->JML_PROJECT)){
            return $returndata->JML_PROJECT;
        }
        else{
            return 0;
        }
    }
    function Portofolio_Total_Project_Value($bu,$tahun){
        $returndata = $this->db->query("select sum(nvl(usd_wes_idr,0)) as PROJECT_VALUE, bu_id from v_usd_idr c inner join projects a on c.project_id=a.project_id right join p_bu b on (a.bu_code=b.bu_code or a.bu_code=b.bu_alias) where bu_id='$bu' and to_char(date_created,'YYYY')='$tahun' group by b.bu_id")->row();
        if(isset($returndata->PROJECT_VALUE)){
            return $returndata->PROJECT_VALUE;
        }
        else{
            return 0;
        }
    }




     function Portofolio_Active_Project($bu,$tahun){
            $result=0;
            $q=$this->db->query("select nvl(count(a.project_id),0) as jml_project, bu_id from projects a
               right join p_bu b on (a.bu_code=b.bu_code or a.bu_code=b.bu_alias)
               where project_status not in('Not Started','Completed') and bu_id='$bu' and to_char(date_created,'YYYY')='$tahun'
               group by bu_id
            ");
          if($q->num_rows() > 0){
            $result = $q->row()->JML_PROJECT;
          }
          return $result;
     }
    function Portofolio_completed_Project($bu,$tahun){
        $result=0;
        $q=$this->db->query("select nvl(count(a.project_id),0) as jml_project, bu_id from projects a
               right join p_bu b on (a.bu_code=b.bu_code or a.bu_code=b.bu_alias)
               where project_status = 'Completed' and bu_id='$bu' and to_char(date_created,'YYYY')='$tahun'
               group by bu_id
            ");
        if($q->num_rows() > 0){
            $result = $q->row()->JML_PROJECT;
        }
        return $result;
    }
    function Portofolio_notstarted_Project($bu,$tahun){
        $result=0;
        $q=$this->db->query("select nvl(count(a.project_id),0) as jml_project, bu_id from projects a
               right join p_bu b on (a.bu_code=b.bu_code or a.bu_code=b.bu_alias)
               where project_status = 'Not Started' and bu_id='$bu' and to_char(date_created,'YYYY')='$tahun'
               group by bu_id
            ");
        if($q->num_rows() > 0){
            $result = $q->row()->JML_PROJECT;
        }
        return $result;
    }


  }
