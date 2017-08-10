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
        $start = "01-DEC-16";
        $end = "20-DEC-16";
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
                  num_business_days c.finish_date, c.finish_date) n_duration,
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
SELECT * from wbs_pool WHERE  ROWNUM <= 99
     
");
        //print_r($query->result());
        $query = $this->db->query("
SELECT * from detail_capture WHERE  project_id='8538862' AND ROWNUM <= 99
     
");
       print_r($query->result());
        $query = $this->db->query("
SELECT * from capture_wbs WHERE  project_id='8538862'  AND  ROWNUM <= 99
     
");
        print_r($query->result());
        $query = $this->db->query("
SELECT * from users WHERE user_id='S201502162' AND ROWNUM <= 99
     
");
      //  print_r($query->result());
        $query = $this->db->query("
SELECT * from projects WHERE project_id='8532760' AND ROWNUM <= 99
     
");
     //   print_r($query->result());

    }
    function test()
    {
        $list=$this->M_Member_Activity->selectTimesheet("8790852");

print_r($list);
    }



}

?>