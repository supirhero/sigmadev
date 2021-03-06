<?php

Class M_notif extends CI_Model {
	function getNotif( $user_id, $time = 0,$filter="" ) {
		$unread = $this->unreadNotif( $user_id );
		if ( $time >= 1 ) {
			$sql = "
            select *
            from  
            ( 
            select * from USER_NOTIF where NOTIF_READ<2 AND user_id='$user_id' AND NOTIF_TIME < $time ORDER BY NOTIF_TIME DESC
            )
            where ROWNUM <= 10
            ";
		} else {
			$sql = "
                select *
                from  
                ( select n.*,u.USER_NAME,coalesce(p.project_name,'') as project_name,coalesce(p.project_status,'') as project_status,
                coalesce(p.project_complete,0) as project_percent 
                  from USER_NOTIF n
                  left join projects p
                  on n.NOTIF_TO = p.project_id 
                  left join USERS u
                  on n.NOTIF_FROM = u.USER_ID 
                  where n.user_id='$user_id' AND  n.NOTIF_READ<2   ORDER BY n.NOTIF_TIME DESC 
                ) 
                where ROWNUM <= 10";
		}

		$query = $this->db->query( $sql );
		$hasil = $query->result_array();

		foreach ( $hasil as $notif ) {
			if (strtolower( $notif["NOTIF_TYPE"] ) == "approve" ) {
				$anu            = intval( $notif["PROJECT_PERCENT"] );
				$percent        = round( $anu, 2 );
				$data["list"][] = [
					"notif_id"       => $notif["NOTIF_ID"],
					"project_id"       => $notif["NOTIF_TO"],
					"project_name"     => $notif["PROJECT_NAME"],
					"project_status"   => $notif["PROJECT_STATUS"],
					"project_complete" => $percent,
					"user_id"          => $notif["NOTIF_FROM"],
					"user_name"        => $notif["USER_NAME"],
					"text"             => "Your timesheet is approved",
					"type"             => $notif["NOTIF_TYPE"],
					"readed"             => $notif["NOTIF_READ"],
					"unixtime"         => $notif["NOTIF_TIME"],
					"datetime"         => date( "Y-m-d h:i", $notif["NOTIF_TIME"] ),
				];
			}
			elseif (strtolower( $notif["NOTIF_TYPE"] ) == "deny" ) {
				$anu            = intval( $notif["PROJECT_PERCENT"] );
				$percent        = round( $anu, 2 );
				$data["list"][] = [
					"notif_id"       => $notif["NOTIF_ID"],
					"project_id"       => $notif["NOTIF_TO"],
					"project_name"     => $notif["PROJECT_NAME"],
					"project_status"   => $notif["PROJECT_STATUS"],
					"project_complete" => $percent,
					"user_id"          => $notif["NOTIF_FROM"],
					"user_name"        => $notif["USER_NAME"],
					"text"             => "Your timesheet is denied",
					"type"             => $notif["NOTIF_TYPE"],
					"readed"             => $notif["NOTIF_READ"],
					"unixtime"         => $notif["NOTIF_TIME"],
					"datetime"         => date( "Y-m-d h:i", $notif["NOTIF_TIME"] ),
				];
			} else {
				$anu            = intval( $notif["PROJECT_PERCENT"] );
				$percent        = round( $anu, 2 );
				$data["list"][] = [
					"notif_id"       => $notif["NOTIF_ID"],
					"project_id"       => $notif["NOTIF_TO"],
					"project_name"     => $notif["PROJECT_NAME"],
					"project_status"   => $notif["PROJECT_STATUS"],
					"project_complete" => $percent,
					"user_id"          => $notif["NOTIF_FROM"],
					"user_name"        => $notif["USER_NAME"],
					"text"             => "has updated timesheet. \n you need approve it",
					"type"             => $notif["NOTIF_TYPE"],
					"readed"             => $notif["NOTIF_READ"],
					"unixtime"         => $notif["NOTIF_TIME"],
					"datetime"         => date( "Y-m-d h:i", $notif["NOTIF_TIME"] ),
				];
			}
		}
		$notif["NOTIF_TIME"] = ( intval( $notif["NOTIF_TIME"] ) >= 1 ) ? $notif["NOTIF_TIME"] : 0;
		$data["info"]        = [
			"current_user_id" => $user_id,
			"total_unread"    => $unread,
			"load_more"       => $notif["NOTIF_TIME"]
		];
		return $data;
	}

	function unreadNotif( $user_id ) {

		$sql = "select count(*) as unread from USER_NOTIF where user_id='$user_id' and notif_read <2";


		$query = $this->db->query( $sql );
		$hasil = $query->row_array();

		return $hasil["UNREAD"];

	}

	function setNotif( $user_id, $notif_id=0 ) {
if($notif_id >=1)
{
	$query = $this->db->query( "update USER_NOTIF set notif_read=2 where user_id='$user_id' AND notif_id='$notif_id' " );
}
else{
	$query = $this->db->query( "update USER_NOTIF set notif_read=1 where user_id='$user_id' AND notif_read=0" );

}
if($query)
{
	return true;
}
else{
	return false;
}

	}

	public function insertNotif( $data ) {
		$time = time();
		$sql  = "INSERT INTO USER_NOTIF (USER_ID,NOTIF_TYPE,NOTIF_FROM,NOTIF_TO, NOTIF_TIME) VALUES (
  '" . $data['USER_ID'] . "',
  'Project',
  " . $data['FROM'] . ",
  " . $data['TO'] . ",
  '" . $time . "')";
		$q    = $this->db->query( $sql );
		$res  = $this->db->query( "select * from USER_NOTIF where USER_ID='" . $data['USER_ID'] . "' AND NOTIF_TIME='$time'" );
		if ( $q->num_rows() > 0 ) {
			return $q->row_array();
		} else {
			return false;
		}
	}

}


?>
