<?php
class M_wbs extends CI_Model {

    function _construct() {
        parent::_construct();
        $this->load->database();
    }

    function tambahwbs($dataarray)
    {
        print_r($dataarray);

        die;
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
                echo $array[$x][count($array[$x])-2];
                $parent =$array[$x][count($array[$x])-2];
            }
            elseif (count($array[$x]) >=2) {
                $h=1;
                $ini="";
                for ($a=0; $a <count($array[$x])-1 ; $a++) {
                    $ini .= $array[$x][$a].".";
                    $h++;

                }
                echo rtrim($ini, ".");
                $parent = rtrim($ini, ".");
            }

            $pro_id =  $dataarray[$i]['PROJECT_ID'];

            $id= $this->db->query("select NVL(max(cast(ID as int))+1, 1) as NEW_ID from WBS_PROJECT where PROJECT_ID='".$pro_id."' ")->row()->NEW_ID;
            $b=$pro_id.'.'.$id;
            $this->db->set('WBS_ID',$b);
            //echo '007'.$x;
            $afrika[$pro_id.'.'.$id] = $cars[$i];
            $afrikan[$cars[$i]] = $pro_id.'.'.$id;
            if (count($array[$x]) >1 ){
                //  echo
                $this->db->set('WBS_PARENT_ID',$afrikan[$parent]);

            }
            else {
                $this->db->set('WBS_PARENT_ID',$pro_id.'.0');
            }

            $x++;


            $this->db->set('PROJECT_ID',$pro_id);
            $start=$dataarray[$i]['START_DATE'];
            $finish=$dataarray[$i]['FINISH_DATE'];
            //  $id=  $this->db->query("select NVL(max(ID)+1, 1) as NEW_ID from WBS")->row()->NEW_ID;
            //  $this->db->set('ID',$id);

            //$this->db->set('IWO_NO',$dataarray[$i]['IWO_NO']);
            $this->db->set('WBS_NAME',$dataarray[$i]['WBS_NAME']);
            //$this->db->set('WBS_DESC',$dataarray[$i]['WBS_DESC']);
            //$this->db->set('PRIORITY',$dataarray[$i]['PRIORITY']);
            //$this->db->set('CALCULATION_TYPE',$dataarray[$i]['CALCULATION_TYPE']);
            //$this->db->set('USER_TAG',$dataarray[$i]['USER_TAG']);
            //$this->db->set('PHASE',$dataarray[$i]['PHASE']);
            //$this->db->set('EFFORT_DRIVEN',$dataarray[$i]['EFFORT_DRIVEN']);
            $this->db->set('PROGRESS_WBS','0');

            $this->db->set('START_DATE',"to_date('$start','DD/MM/YYYY')",false);
            //$this->db->set('ACTUAL_START_DATE',$dataarray[$i]['ACTUAL_START_DATE']);
            $this->db->set('FINISH_DATE',"to_date('$finish','DD/MM/YYYY')",false);
            //$this->db->set('ACTUAL_FINISH_DATE',$dataarray[$i]['ACTUAL_FINISH_DATE']);
            $this->db->set('DURATION',$dataarray[$i]['DURATION']);
            $this->db->set('WORK',$dataarray[$i]['WORK']);
            //$this->db->set('MILESTONE',$dataarray[$i]['MILESTONE']);
            //$this->db->set('WORK_COMPLETE',$dataarray[$i]['WORK_COMPLETE']);
            $this->db->set('WORK_COMPLETE',($dataarray[$i]['DURATION']*8));
            //$this->db->set('CONSTRAINT_TYPE',$dataarray[$i]['CONSTRAINT_TYPE']);
            //$this->db->set('CONSTRAINT_DATE',$dataarray[$i]['CONSTRAINT_DATE']);
            //$this->db->set('DEADLINE',$dataarray[$i]['DEADLINE']);

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

        }
        //return json_encode($number[count($number)-1]);
    }

}
?>
