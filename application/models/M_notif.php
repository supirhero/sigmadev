<?php

Class M_notif extends CI_Model{
    function getNotif($user_id,$time = 0){
        $unread = $this->unreadNotif($user_id);
        if($time >= 1)
        {
	        $sql="
            select *
            from  
            ( 
            select * from USER_NOTIF where user_id='$user_id' AND NOTIF_TIME < $time ORDER BY NOTIF_TIME DESC
            )
            where ROWNUM <= 10
            ";
        }
        else{
	        $sql="
                select *
                from  
                ( select USER_NOTIF.*,p.project_name,p.project_status,p.project_complete as project_percent 
                  from USER_NOTIF
                  left join projects p
                  on user_notif.NOTIF_TO = p.project_id 
                  where user_id='$user_id'   ORDER BY NOTIF_TIME DESC 
                ) 
                where ROWNUM <= 10";
        }

        $query = $this->db->query($sql);
        $hasil = $query->result_array();
        foreach($hasil as $notif)
        {
	        $query = $this->db->query("select * from USERS where user_id='$notif[NOTIF_FROM]'");
	        $user = $query->row_array();
	        $data["list"][]=[
	            "project_id"=>$notif["NOTIF_TO"],
                "project_name"=>$notif["PROJECT_NAME"],
                "project_status"=>$notif["PROJECT_STATUS"],
                "project_complete"=>$notif["PROJECT_COMPLETE"],
                "user_id"=>$user["USER_ID"],
                "user_name"=>$user["USER_NAME"],
                "text"=>"has updated timesheet. \n you need approve it",
                "unixtime"=>$notif["NOTIF_TIME"],
                "datetime"=>date("Y-m-d h:i",$notif["NOTIF_TIME"]),
            ];
        }
        $data["info"] = ["current_user_id"=>$notif["USER_ID"],"total_unread"=>$unread,"load_more"=>$notif["NOTIF_TIME"]];
        return $data;
    }
    function setNotif($user_id){

        $sql="select count(*) as unread from USER_NOTIF where user_id='$user_id' and notif_read = 0";
	    $query = $this->db->query("update USER_NOTIF set notif_read=1 where user_id='$user_id'");

    }
    function unreadNotif($user_id){

	        $sql="select count(*) as unread from USER_NOTIF where user_id='$user_id' and notif_read = 0";


        $query = $this->db->query($sql);
        $hasil = $query->row_array();
        return $hasil["UNREAD"];

    }

    public function insertNotif($data){
        $time = time();
        $sql="INSERT INTO USER_NOTIF (USER_ID,NOTIF_TYPE,NOTIF_FROM,NOTIF_TO, NOTIF_TIME) VALUES (
  '".$data['USER_ID']."',
  'Project',
  ".$data['FROM'].",
  ".$data['TO'].",
  '".$time."')";
        $q=$this->db->query($sql);
        $res=$this->db->query("select * from USER_NOTIF where USER_ID='".$data['USER_ID']."' AND NOTIF_TIME='$time'");
        if ($q->num_rows()>0) {
          return $q->row_array();
        }else{
          return false;
        }
    }

}



?>
