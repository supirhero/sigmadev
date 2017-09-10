<?php
class M_wbs extends CI_Model {

    function _construct() {
        parent::_construct();
        $this->load->database();
        $this->load->model('M_detail_project');
    }

    function tambahwbs($dataarray)
    {
        for($x=1;$x<count($dataarray);$x++){
            $number []= $dataarray[$x]['anjay'];

        }
        $x=0;

        for($i=1;$i<count($dataarray);$i++){

            $cars =  $number[count($number)-1];

            //return $cars;

            //$a='iwo'.$x;
            $file = array_filter((explode('.',$cars[$i])));
            $array[]=$file;
            if (count($array[$x]) >1 and count($array[$x]) == 2) {
                $array[$x][count($array[$x])-2];
                $parent =$array[$x][count($array[$x])-2];
            }
            elseif (count($array[$x]) >=2) {
                $h=1;
                $ini="";
                for ($a=0; $a <count($array[$x])-1 ; $a++) {
                    $ini .= $array[$x][$a].".";
                    $h++;

                }
               rtrim($ini, ".");
                $parent = rtrim($ini, ".");
            }

            $pro_id =  $dataarray[$i]['PROJECT_ID'];

            $id= $this->db->query("select NVL(max(cast(ID as int))+1, 1) as NEW_ID from WBS_PROJECT where PROJECT_ID='".$pro_id."' ")->row()->NEW_ID;
            $b=$pro_id.'.'.$id;
            $this->db->set('WBS_ID',$b);
            $afrika[$pro_id.'.'.$id] = $cars[$i];
            $afrikan[$cars[$i]] = $pro_id.'.'.$id;
            if (count($array[$x]) >1 ){
                $this->db->set('WBS_PARENT_ID',$afrikan[$parent]);

            }
            else {
                $this->db->set('WBS_PARENT_ID',$pro_id.'.0');
            }

            $x++;

            $this->db->set('PROJECT_ID',$pro_id);
            $start=$dataarray[$i]['START_DATE'];
            $finish=$dataarray[$i]['FINISH_DATE'];
            $this->db->set('WBS_NAME',$dataarray[$i]['WBS_NAME']);
            $this->db->set('PROGRESS_WBS','0');

            $this->db->set('START_DATE',"to_date('$start','DD/MM/YYYY')",false);
            $this->db->set('FINISH_DATE',"to_date('$finish','DD/MM/YYYY')",false);
            $this->db->set('DURATION',$dataarray[$i]['DURATION']);
            $this->db->set('WORK',$dataarray[$i]['WORK']);
            $this->db->set('WORK_COMPLETE',($dataarray[$i]['DURATION']*8));


            //$this->db->set('ACHIEVEMENT',$dataarray[$i]['ACHIEVEMENT']);
            //$this->db->set('ID',$dataarray[$i]['ID']);
            //$this->db->set('TEXT',$dataarray[$i]['TEXT']);
            //$this->db->set('PROGRESS',$dataarray[$i]['PROGRESS']);
            //$this->db->set('SORTORDER',$dataarray[$i]['SORTORDER']);
            //$this->db->set('PARENT',$dataarray[$i]['PARENT']);
            //$this->db->set('PLANNED_START',$dataarray[$i]['PLANNED_START']);
            //$this->db->set('PLANNED_END',$dataarray[$i]['PLANNED_END']);
            //$this->db->set('END_DATE',$dataarray[$i]['END_DATE']);
            $this->db->insert('WBS');

            //get all wbs data from new wbs
            $selWBS=$this->M_detail_project->getWBSselected($b);
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
        //return json_encode($number[count($number)-1]);
    }

    function tambahwbsTemp($dataarray,$rh_id)
    {
        for($x=1;$x<count($dataarray);$x++){
            $number []= $dataarray[$x]['anjay'];

        }
        $x=0;


        for($i=1;$i<count($dataarray);$i++){

            $cars =  $number[count($number)-1];

            //return $cars;

            //$a='iwo'.$x;
            $file = array_filter((explode('.',$cars[$i])));
            $array[]=$file;
            if (count($array[$x]) >1 and count($array[$x]) == 2) {
                $array[$x][count($array[$x])-2];
                $parent =$array[$x][count($array[$x])-2];
            }
            elseif (count($array[$x]) >=2) {
                $h=1;
                $ini="";
                for ($a=0; $a <count($array[$x])-1 ; $a++) {
                    $ini .= $array[$x][$a].".";
                    $h++;

                }
                rtrim($ini, ".");
                $parent = rtrim($ini, ".");
            }

            $pro_id =  $dataarray[$i]['PROJECT_ID'];

            $id= $this->db->query("select NVL(max(cast(ID as int))+1, 1) as NEW_ID from (SELECT
                                    WBS_ID,
                                    WBS_PARENT_ID,
                                    SUBSTR(WBS_ID, INSTR(wbs_id, '.')+1) AS ID,
                                    SUBSTR(WBS_PARENT_ID, INSTR(wbs_id, '.')+1) AS PARENT_ID,
                                    PROJECT_ID,
                                    WBS_NAME,
                                    WBS_DESC,
                                    WORK,
                                    DURATION,
                                    START_DATE,
                                    FINISH_DATE,
                                    WORK_COMPLETE,
                                    ACHIEVEMENT
                                    FROM
                                    WBS
                                    UNION 
                                    SELECT
                                    WBS_ID,
                                    WBS_PARENT_ID,
                                    SUBSTR(WBS_ID, INSTR(wbs_id, '.')+1) AS ID,
                                    SUBSTR(WBS_PARENT_ID, INSTR(wbs_id, '.')+1) AS PARENT_ID,
                                    PROJECT_ID,
                                    WBS_NAME,
                                    WBS_DESC,
                                    WORK,
                                    DURATION,
                                    START_DATE,
                                    FINISH_DATE,
                                    WORK_COMPLETE,
                                    ACHIEVEMENT
                                    FROM
                                    TEMPORARY_WBS where rh_id = '$rh_id'
                                    ) 
                                    where PROJECT_ID='".$pro_id."' ")->row()->NEW_ID;
            $b=$pro_id.'.'.$id;
            $this->db->set('WBS_ID',$b);
            $afrika[$pro_id.'.'.$id] = $cars[$i];
            $afrikan[$cars[$i]] = $pro_id.'.'.$id;
            if (count($array[$x]) >1 ){
                $this->db->set('WBS_PARENT_ID',$afrikan[$parent]);

            }
            else {
                $this->db->set('WBS_PARENT_ID',$pro_id.'.0');
            }

            $x++;

            $this->db->set('PROJECT_ID',$pro_id);
            $start=$dataarray[$i]['START_DATE'];
            $finish=$dataarray[$i]['FINISH_DATE'];
            $this->db->set('WBS_NAME',$dataarray[$i]['WBS_NAME']);
            $this->db->set('ACTION','create');
            $this->db->set('PROGRESS_WBS','0');

            $this->db->set('START_DATE',"to_date('$start','DD/MM/YYYY')",false);
            $this->db->set('FINISH_DATE',"to_date('$finish','DD/MM/YYYY')",false);
            $this->db->set('DURATION',$dataarray[$i]['DURATION']);
            $this->db->set('WORK',$dataarray[$i]['WORK']);
            $this->db->set('WORK_COMPLETE',($dataarray[$i]['DURATION']*8));
            $this->db->set('RH_ID',$rh_id);

            //$this->db->set('ACHIEVEMENT',$dataarray[$i]['ACHIEVEMENT']);
            //$this->db->set('ID',$dataarray[$i]['ID']);
            //$this->db->set('TEXT',$dataarray[$i]['TEXT']);
            //$this->db->set('PROGRESS',$dataarray[$i]['PROGRESS']);
            //$this->db->set('SORTORDER',$dataarray[$i]['SORTORDER']);
            //$this->db->set('PARENT',$dataarray[$i]['PARENT']);
            //$this->db->set('PLANNED_START',$dataarray[$i]['PLANNED_START']);
            //$this->db->set('PLANNED_END',$dataarray[$i]['PLANNED_END']);
            //$this->db->set('END_DATE',$dataarray[$i]['END_DATE']);
            $this->db->insert('TEMPORARY_WBS');


        }
        //return json_encode($number[count($number)-1]);
    }

}
?>
