<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

$hook['pre_controller'] = array(
    'class' => 'Classname',
    'function' => 'functionname',
    'filename' => 'filename.php',
    'filepath' => 'hooks',
);

function privilege(){
    echo "tes";
    die;
    //PRIVILEGE CHECKER
    $url_dest = strtolower($this->uri->segment(1)."/".$this->uri->segment(2));
    $privilege = $this->db->query("select al.access_id,al.type,au.access_url,pal.privilege
                                        from access_list al
                                        join access_url au
                                        on al.access_id = au.access_id
                                        join profile_access_list pal
                                        on
                                        pal.access_id = au.access_id
                                        where pal.profile_id = ".$this->datajson['userdata']['PROF_ID']."
                                        ")->result_array();
    foreach($privilege as $priv){
        //jika akses url ada di dalam db
        if($priv['ACCESS_URL'] == $url_dest){
            //jika akses tipe nya business
            if($priv['TYPE'] == 'BUSINESS'){
                if($priv['PRIVILEGE'] == 'all_bu'){
                }
                elseif($priv['PRIVILEGE'] == 'only_bu'){
                    switch ($priv['ACCESS_ID']){
                        case '1':
                            $bu_id = $this->db->query(" select p_bu.bu_id 
                                                            from (select wp_id,wbs_id from wbs_pool
                                                            union 
                                                            select wp_id,wbs_id from temporary_wbs_pool) wbs_pool
                                                            join (select wbs_id,project_id from wbs union select wbs_id,project_id from temporary_wbs) wbs
                                                            on wbs_pool.wbs_id = wbs.wbs_id 
                                                            join projects
                                                            on wbs.project_id = projects.project_id
                                                            join p_bu
                                                            on projects.bu_code = p_bu.bu_code
                                                            where wbs_pool.wp_id = '".$_POST['WP_ID']."'
                                                            ");

                            break;
                        case '2':
                            $bu_id = $this->db->query("select bu_id from p_bu where bu_code = '".$_POST['bu_code']."'")->row()->BU_ID;
                            if($this->datajson['userdata']['BU_ID'] == $bu_id){

                            }
                            else{
                                $returndata['status'] = 'denied';
                                $returndata['message'] = 'you dont have permission to access this api';
                                echo json_encode($returndata);
                                die;
                            }
                            break;
                        case '3':

                            break;

                    }
                }
                else{

                }

            }
        }
    }
}