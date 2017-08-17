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
        $query = $this->db->query("
        select case when ev= 0 AND project_status = 'Completed'
then pv else ev end as ev, a.project_id from (
SELECT  SUM (ev) AS ev, a.project_id, project_status
       FROM (SELECT     b.project_id,p.project_status,
                        CASE 
                           WHEN (    b.work_percent_complete IS NOT NULL
                                 AND work_percent_complete != 0
                                )
                              THEN   TO_NUMBER (b.work_percent_complete)
                                   * DURATION
                                   * 8
                                   / 100  
                                   * TO_NUMBER
                                        (CASE
                                            WHEN (    b.resource_wbs > 0
                                                  AND b.resource_wbs IS NOT NULL
                                                 )
                                               THEN b.resource_wbs
                                            ELSE 1
                                         END
                                        )
                           ELSE TO_NUMBER (ROUND (  progress_wbs
                                                  * work_complete
                                                  / 100,
                                                  2
                                                 )
                                          )
                        END AS ev,
                        CONNECT_BY_ISLEAF AS leaf
                   FROM wbs b    join projects p on b.project_id=p.project_id
                  WHERE CONNECT_BY_ISLEAF = 1
             CONNECT BY b.wbs_parent_id = PRIOR b.wbs_id
             START WITH b.wbs_parent_id IS NULL
               ORDER SIBLINGS BY b.wbs_id) a
   GROUP BY a.project_id,project_status) a
   JOIN
            v_pv_project b ON a.project_id = b.project_id
");
        print_r($query->result());

        //print_r($list);

    }
    function test_pv()
    {
        $start = "2016-12-01";
        $end = "2016-12-30";
        $query = $this->db->query("
select sum(pv) as pv, project_id, min(start_date) as aza, max(start_date) as anu, max(finish_date) as anjay from (
             select ( select current_duration * CASE
    WHEN (
      b.resource_wbs > 0
      AND b.resource_wbs IS NOT NULL
    ) THEN
      b.resource_wbs
    ELSE
      1
    END * 8 from (SELECT d.wbs_id, d.start_date, d.finish_date,
          NVL (e.n_holiday, 0) n_holiday,
          (d.n_duration - NVL (e.n_holiday, 0)) count_duration,
          CASE
             WHEN TRUNC (d.start_date) > TRUNC (SYSDATE)
                THEN 0
             WHEN TRUNC (d.finish_date) <  TRUNC (SYSDATE)
                THEN  (d.n_duration - NVL (e.n_holiday, 0))
             ELSE NVL ((d.n_duration_today - NVL (f.n_holiday_today, 0)),
                       (d.n_duration - NVL (e.n_holiday, 0)
                       )
                      )
          END AS current_duration
     FROM (SELECT c.wbs_id, c.project_id, c.start_date, c.finish_date,
                  num_business_days (c.start_date, c.finish_date) n_duration,
                  num_business_days (c.start_date, SYSDATE) n_duration_today
             FROM wbs c) d,
          (SELECT   a.wbs_id, COUNT (b.dt) n_holiday
               FROM wbs a, v_holiday_excl_weekend b
              WHERE b.dt BETWEEN a.start_date AND a.finish_date
           GROUP BY a.wbs_id) e,
          (SELECT   a1.wbs_id, COUNT (b1.dt) n_holiday_today
               FROM wbs a1, v_holiday_excl_weekend b1
              WHERE b1.dt BETWEEN a1.start_date AND SYSDATE
           GROUP BY a1.wbs_id) f
    WHERE d.wbs_id = e.wbs_id(+) AND d.wbs_id = f.wbs_id(+) ) a where a.WBS_ID=b.WBS_ID)as pv,b.* from wbs  b
                  WHERE b.project_id=8538862 AND CONNECT_BY_ISLEAF = 1
             CONNECT BY b.wbs_parent_id = PRIOR b.wbs_id
             START WITH b.wbs_parent_id IS NULL) group by project_id 
");
        print_r($query->result());

    }
    function test_pv2()
    {
        $project_id = "8538862";
        $start = "2016-12-01";
        $end = "2016-12-30";
        $query = $this->db->query("
WITH date_range AS (
    SELECT  ACTUAL_START_DATE as start_date
           ,ACTUAL_END_DATE as end_date
    FROM    PROJECTS where project_id='$project_id'
    )
SELECT  t2.\"Week\",t2.\"startdate\",t2.\"enddate\",
(SELECT COUNT (b1.dt)
               FROM  v_holiday_excl_weekend b1
              WHERE b1.dt BETWEEN t2.\"startdate\" AND t2.\"enddate\" 
             )*(SELECT CASE
    WHEN (
      sum(RESOURCE_WBS) > 0
      AND sum(RESOURCE_WBS) IS NOT NULL
    ) THEN
      sum(RESOURCE_WBS)*sum(duration)
    ELSE
      1
      END from wbs  WHERE project_id='$project_id'
      GROUP BY project_id 
     )
             
            FROM   (SELECT  LEVEL \"Week\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') \"startdate\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') + 6 \"enddate\"
       ,TO_CHAR(start_date + (7 * (LEVEL - 1)),'IW') \"Iso Week\"
FROM   date_range t2
CONNECT BY LEVEL <= (TRUNC(end_date,'IW') - TRUNC(start_date,'IW')) / 7 + 1) t2
");
        print_r($query->result());

    }
    function test_pv_total()
    {
        $project_id = "8538862";
        $start = "2016-12-01";
        $end = "2016-12-30";
        $query = $this->db->query("
(SELECT CASE
    WHEN (
      sum(RESOURCE_WBS) > 0
      AND sum(RESOURCE_WBS) IS NOT NULL
    ) THEN
      sum(RESOURCE_WBS*duration*8)
    ELSE
      sum(duration*8)

      END from wbs  WHERE project_id='$project_id'
      GROUP BY project_id 
     )");
        print_r($query->result());

    }
    function test_wbs()
    {
        $query = $this->db->query("
SELECT d.wbs_id, d.start_date, d.finish_date,
          NVL (e.n_holiday, 0) n_holiday,
          (d.n_duration - NVL (e.n_holiday, 0)) count_duration,
          CASE
             WHEN TRUNC (d.start_date) > TRUNC (SYSDATE)
                THEN 0
             WHEN TRUNC (d.finish_date) <  TRUNC (SYSDATE)
                THEN  (d.n_duration - NVL (e.n_holiday, 0))
             ELSE NVL ((d.n_duration_today - NVL (f.n_holiday_today, 0)),
                       (d.n_duration - NVL (e.n_holiday, 0)
                       )
                      )
          END AS current_duration
     FROM (SELECT c.wbs_id, c.project_id, c.start_date, c.finish_date,
                  num_business_days (c.start_date, c.finish_date) n_duration,
                  num_business_days (c.start_date, SYSDATE) n_duration_today
             FROM wbs c) d,
          (SELECT   a.wbs_id, COUNT (b.dt) n_holiday
               FROM wbs a, v_holiday_excl_weekend b
              WHERE b.dt BETWEEN a.start_date AND a.finish_date
           GROUP BY a.wbs_id) e,
          (SELECT   a1.wbs_id, COUNT (b1.dt) n_holiday_today
               FROM wbs a1, v_holiday_excl_weekend b1
              WHERE b1.dt BETWEEN a1.start_date AND SYSDATE
           GROUP BY a1.wbs_id) f
    WHERE d.wbs_id = e.wbs_id(+) AND d.wbs_id = f.wbs_id(+) 
    AND d.project_id=8538862  AND ROWNUM <= 30
     
");
        print_r($query->result());

    }
    function anu()
    {

        $query = $this->db->query("
SELECT * from resource_pool WHERE project_id='8532760' AND   ROWNUM <= 99
     
");
       // print_r($query->result());
        $query = $this->db->query("
SELECT * from v_holiday_excl_weekend WHERE  ROWNUM <= 99
     
");
        //print_r($query->result());
        $query = $this->db->query("
SELECT * from detail_capture WHERE  project_id='8538862' AND ROWNUM <= 99
     
");
     //  print_r($query->result());
        $query = $this->db->query("
SELECT * from wbs WHERE  project_id='7778226'  AND  ROWNUM <= 99
     
");
       // print_r($query->result());
        $query = $this->db->query("
with sample_data as ( SELECT 1 AS rate FROM dual
UNION ALL SELECT 2 FROM dual
UNION ALL SELECT 3 FROM dual
UNION ALL SELECT 9 FROM dual
UNION ALL SELECT 6 FROM dual
UNION ALL SELECT 6 FROM dual
UNION ALL SELECT 7 FROM dual)
select 
       rate, rate - lag(rate, 1, rate) over ( order by rate) issue
from   sample_data 
");
        print_r($query->result());
        $query = $this->db->query("
SELECT    COUNT (b1.dt) n_holiday_today
               FROM wbs a1, v_holiday_excl_weekend b1
              WHERE a1.project_id='8538862' AND b1.dt BETWEEN a1.start_date AND a1.finish_date
           GROUP BY a1.wbs_id     
");
      //  print_r($query->result());
        $query = $this->db->query("
select * from all_source where name = 'DAILY_UPDATE'
     
");
     //   print_r($query->result());

    }
    function test()
    {
        //  print_r($query->result());
        /*
        $query = $this->db->query("
CREATE TABLE tb_rekap_project
( project_id number(10) NOT NULL,
  tanggal date default sysdate not null,
  pv number(10) NOT NULL,
  ev number(10) NOT NULL,
  ac number(10) NOT NULL
)    
");
     */     // print_r($query->result());
/*
        $query = $this->db->query("
SELECT *
FROM tb_pv_project pv
 JOIN tb_ev_project ev
  ON ev.project_id = pv.project_id
 JOIN  tb_ac_project ac
  ON ac.project_id = pv.project_id
WHERE ROWNUM <= 99    
");
*/
       //   print_r($query->result());

        $query = $this->db->query("
WITH date_range AS (
select trunc(TS_DATE) as tanggal, count(HOUR_TOTAL) as hour_total
  from TIMESHEET
 where TS_DATE between date '2017-01-02' and date '2017-01-31'
 group by trunc(TS_DATE)
 order by trunc(TS_DATE)
    )
SELECT  LEVEL \"Week\"
,hour_total,tanggal,
     (select sum(t1.hour_total) hour_totals from date_range t1 where t1.tanggal <= t2.tanggal )

FROM   date_range t2
CONNECT BY LEVEL <= (TRUNC(tanggal,'IW') - TRUNC(tanggal,'IW')) / 7 + 1
");

        //  print_r($query->result());
$project_id = "8538862";
        $query = $this->db->query("

insert into tb_rekap_project 
select tb_pv_project.project_id as project_id,SYSDATE as tanggal,tb_pv_project.pv as pv, tb_ev_project.ev as ev, tb_ac_project.ac as ac from tb_pv_project
left join tb_ev_project
on tb_ev_project.project_id=tb_pv_project.project_id
left join tb_ac_project
on tb_ac_project.project_id=tb_pv_project.project_id
");
          print_r($query->result());

          $sql = "CREATE OR REPLACE PROCEDURE PROUDS.insert_tb_rekap_project as
begin
commit;
insert into tb_rekap_project 
select * from tb_pv_project
left join tb_ev_project
on tb_ev_project.project_id=tb_pv_project.project_id
left join tb_ac_project
on tb_ac_project.project_id=tb_pv_project.project_id
commit;
end;
";
    }
    function spi()
    {
        $project_id = "8538862";
        $query = $this->db->query("
WITH date_range AS (
    SELECT  ACTUAL_START_DATE as start_date
           ,ACTUAL_END_DATE as end_date
    FROM    PROJECTS where project_id='$project_id'
    )
SELECT  t2.\"Week\",t2.\"startdate\",t2.\"enddate\",
            (select sum(t1.pv)- lag(sum(t1.pv), 1, sum(t1.pv)) over (order by t2.\"startdate\") from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as pv,
            (select sum(t1.ev)- lag(sum(t1.ev), 1, sum(t1.ev)) over (order by t2.\"startdate\") from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev,
            (select (sum(t1.ev)- lag(sum(t1.ev), 1, sum(t1.ev)) over (order by t2.\"startdate\"))/(sum(t1.pv)- lag(sum(t1.pv), 1, sum(t1.pv)) over (order by t2.\"startdate\"))  from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as spi

            FROM   (SELECT  LEVEL \"Week\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') \"startdate\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') + 6 \"enddate\"
       ,TO_CHAR(start_date + (7 * (LEVEL - 1)),'IW') \"Iso Week\"
FROM   date_range t2
CONNECT BY LEVEL <= (TRUNC(end_date,'IW') - TRUNC(start_date,'IW')) / 7 + 1) t2
");
        $result = $query->result();
        echo print_r($result);
    }
    function cpi()
    {
        $project_id = "8538862";

        $query = $this->db->query("
WITH date_range AS (
    SELECT  ACTUAL_START_DATE as start_date
           ,ACTUAL_END_DATE as end_date
    FROM    PROJECTS where project_id='$project_id'
    )
SELECT  t2.\"Week\",t2.\"startdate\",t2.\"enddate\",
            (select sum(t1.ac)- lag(sum(t1.ac), 1, sum(t1.ac)) over ( order by t2.\"startdate\") from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as pv,
            (select sum(t1.ev)- lag(sum(t1.ev), 1, sum(t1.ev)) over ( order by t2.\"startdate\") from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev,
            (select (sum(t1.ev)- lag(sum(t1.ev), 1, sum(t1.ev)) over ( order by t2.\"startdate\"))/(sum(t1.ac)- lag(sum(t1.ac), 1, sum(t1.ac)) over ( order by t2.\"startdate\")) from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as cpi

            FROM   (SELECT  LEVEL \"Week\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') \"startdate\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') + 6 \"enddate\"
       ,TO_CHAR(start_date + (7 * (LEVEL - 1)),'IW') \"Iso Week\"
FROM   date_range t2
CONNECT BY LEVEL <= (TRUNC(end_date,'IW') - TRUNC(start_date,'IW')) / 7 + 1) t2
");
        $result = $query->result();
        echo print_r($result);
    }
    function s_curve()
    {
        $project_id = "8538862";
        $query = $this->db->query("
(SELECT CASE
    WHEN (
      sum(RESOURCE_WBS) > 0
      AND sum(RESOURCE_WBS) IS NOT NULL
    ) THEN
      sum(RESOURCE_WBS*duration*8)
    ELSE
      sum(duration*8)

      END as total from wbs  WHERE project_id='$project_id'
      GROUP BY project_id 
     )");
        $total_pv = $query->row()->TOTAL;
        $query = $this->db->query("
WITH date_range AS (
    SELECT  ACTUAL_START_DATE as start_date
           ,ACTUAL_END_DATE as end_date
    FROM    PROJECTS where project_id='$project_id'
    )

        
SELECT  t2.\"Week\",t2.\"startdate\",t2.\"enddate\",
            (select sum(t1.pv) ac from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as pv,
            (select sum(t1.ev) ev from tb_rekap_project t1 where project_id='$project_id' and t1.tanggal between t2.\"startdate\" and t2.\"enddate\" ) as ev

            FROM   (SELECT  LEVEL \"Week\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') \"startdate\"
       ,TRUNC(start_date + (7 * (LEVEL - 1)),'IW') + 4 \"enddate\"
       ,TO_CHAR(start_date + (7 * (LEVEL - 1)),'IW') \"Iso Week\"
FROM   date_range t2
CONNECT BY LEVEL <= (TRUNC(end_date,'IW') - TRUNC(start_date,'IW')) / 7 + 1) t2
");
        $result = $query->result();
$results = [];
        foreach ($result as $key => $val)
        {
            foreach ($val as $week => $valz)
            {
                $results[$key][$week]= $valz;
            }
            $results[$key]["pv_percent"]=round($val->PV/$total_pv*100);
            $results[$key]["ev_percent"]=round($val->EV/$total_pv*100);
        }

print_r($results);
    }


}

?>