<?php

Class M_issue extends CI_Model {
	function selectIssue( $id = '' ) {
		if ( empty( $id ) ) {
			$query = $this->db->query( "select a.ISSUE_ID,
a.user_id,
c.user_name,
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
on a.user_id=c.user_id" );
		} else {
			$query = $this->db->query( "select a.ISSUE_ID,
a.user_id,
c.user_name,
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
on a.user_id=c.user_id where b.PROJECT_ID='" . $id . "'" );
		}

		$hasil = $query->result_array();

		return $hasil;

	}

	function selectReply( $id ) {
		$query = $this->db->query( "SELECT * FROM MANAGE_ISSUE WHERE ISSUE_ID='" . $id . "'" );
		$hasil = $query->result_array();

		return $hasil;
	}

	function getIssue( $id ) {
		$result;
		$sql = "select * FROM  MANAGE_ISSUE WHERE ISSUE_ID='" . $id . "'";
		$q   = $this->db->query( $sql );
		if ( $q->num_rows() > 0 ) {
			$result = $q->row();
		}

		return $result;
	}

	function getBUVP( $USER_ID ) {
		$sql = "select BU_ID from USERS where USER_ID='" . $USER_ID . "' and ROWNUM <=1 ";
		$q   = $this->db->query( $sql );
		if ( $q->num_rows() > 0 ) {
			$result = $q->row()->BU_ID;

			return $result;
		}
	}

	function getNamePM( $USER_ID ) {
		$sql = "select USER_NAME from USERS where USER_ID='" . $USER_ID . "' and ROWNUM <=1 ";
		$q   = $this->db->query( $sql );
		if ( $q->num_rows() > 0 ) {
			$result = $q->row()->USER_NAME;

			return $result;
		}
	}

	function getUserIDVP( $bu ) {
		$sql = "select bu_head from p_bu a inner join users b on a.bu_head=b.USER_ID
  where a.bu_id='" . $bu . "' and ROWNUM <=1 ";
		$q   = $this->db->query( $sql );
		if ( $q->num_rows() > 0 ) {
			$result = $q->row()->BU_HEAD;

			return $result;
		}
	}

	function getUserNameVP( $USER_VP ) {
		$sql = "select USER_NAME from USERS where USER_ID='" . $USER_VP . "' and ROWNUM <=1 ";
		$q   = $this->db->query( $sql );
		if ( $q->num_rows() > 0 ) {
			$result = $q->row()->USER_NAME;

			return $result;
		}
	}

	function getEmailVP( $USER_VP ) {
		$sql = "select EMAIL from USERS where USER_ID='" . $USER_VP . "' and ROWNUM <=1 ";
		$q   = $this->db->query( $sql );
		if ( $q->num_rows() > 0 ) {
			$result = $q->row()->EMAIL;

			return $result;
		}
	}

	function getProjectName( $data ) {
		$sql = "select PROJECT_NAME from PROJECTS where PROJECT_ID='" . $data['PROJECT_ID'] . "' and ROWNUM <=1 ";
		$q   = $this->db->query( $sql );
		if ( $q->num_rows() > 0 ) {
			$result = $q->row()->PROJECT_NAME;

			return $result;
		}
	}

	function selectDetIssue( $id ) {

		$query = $this->db->query( "
     SELECT DETAIL_ISSUE.USER_ID, USERS.USER_NAME, PROFILE.PROF_ID, PROFILE.PROF_NAME, PROJECTS.PROJECT_NAME, DETAIL_ISSUE.SUBJECT, DETAIL_ISSUE.NOTE,
DETAIL_ISSUE.EVIDENCE, DETAIL_ISSUE.STATUS, DETAIL_ISSUE.PRIORITY, DETAIL_ISSUE.DURATION, DETAIL_ISSUE.DATE_ISSUE
from DETAIL_ISSUE
join USERS on DETAIL_ISSUE.USER_ID=USERS.USER_ID
join profile on USERS.prof_id=PROFILE.prof_id
join PROJECTS ON PROJECTS.PROJECT_ID=DETAIL_ISSUE.PROJECT_ID
WHERE ISSUE_ID='" . $id . "'
     " );
		$hasil = $query->result_array();

		return $hasil;

		/*
		$this->db->where('ISSUE_ID', $id);
		$query= $this->db->get('DETAIL_ISSUE');
		$hasil = $query->result_array();
		return $hasil;
		*/
	}

	function getMaxIssue() {
		return $this->db->query( "select NVL(max(ISSUE_ID)+1, 1) as NEW_ID from MANAGE_ISSUE" )->row()->NEW_ID;
	}

	function getMaxDetIssue() {
		return $this->db->query( "select NVL(max(ID)+1, 1) as NEW_ID from DETAIL_ISSUE" )->row()->NEW_ID;
	}

	public function insertIssue( $data ) {

		$date = date( "m/d/Y" );
		$this->db->set( 'ISSUE_ID', $data['ISSUE_ID'] );
		$this->db->set( 'NOTE', $data['NOTE'] );
		$this->db->set( 'PROJECT_ID', $data['PROJECT_ID'] );
		$this->db->set( 'SUBJECT', $data['SUBJECT'] );
		$this->db->set( 'USER_ID', $data['USER_ID'] );
		//$this->db->set('EVIDENCE',$data['EVIDENCE']);
		$this->db->set( 'PRIORITY', $data['PRIORITY'] );
		$this->db->set( 'STATUS', $data['STATUS'] );
		$this->db->set( 'DATE_ISSUE', "to_timestamp('" . date( "d-m-Y H:i:s" ) . "','DD-MM-YYYY HH24:MI:SS')", false );

		$this->db->insert( "MANAGE_ISSUE" );

	}


	public function insertIssueHigh( $data ) {

		$date = date( "m/d/Y" );
		$this->db->set( 'ISSUE_ID', $data['ISSUE_ID'] );
		$this->db->set( 'NOTE', $data['NOTE'] );
		$this->db->set( 'PROJECT_ID', $data['PROJECT_ID'] );
		$this->db->set( 'SUBJECT', $data['SUBJECT'] );
		$this->db->set( 'USER_ID', $data['USER_ID'] );
		//$this->db->set('EVIDENCE',$data['EVIDENCE']);
		$this->db->set( 'PRIORITY', $data['PRIORITY'] );
		$this->db->set( 'STATUS', $data['STATUS'] );
		$this->db->set( 'DATE_ISSUE', "to_timestamp('" . date( "d-m-Y H:i:s" ) . "','DD-MM-YYYY HH24:MI:SS')", false );

		$this->db->insert( "MANAGE_ISSUE" );

	}

	public function insertIssue2( $data ) {

		$date = date( "m/d/Y H:i:s" );
		$this->db->set( 'ISSUE_ID', $data['ISSUE_ID'] );
		$this->db->set( 'NOTE', $data['NOTE'] );
		$this->db->set( 'PROJECT_ID', $data['PROJECT_ID'] );
		$this->db->set( 'SUBJECT', $data['SUBJECT'] );
		$this->db->set( 'USER_ID', $data['USER_ID'] );
		$this->db->set( 'EVIDENCE', $data['EVIDENCE'] );
		$this->db->set( 'PRIORITY', $data['PRIORITY'] );
		$this->db->set( 'STATUS', $data['STATUS'] );
		$this->db->set( 'DATE_ISSUE', "to_timestamp('" . date( "d-m-Y H:i:s" ) . "','DD-MM-YYYY HH24:MI:SS')", false );

		//$data['PROJECT_ID']   = $this->input->post("PROJECT_ID");

		$this->db->insert( "MANAGE_ISSUE" );

	}

	function updateStatus( $issue_id, $data ) {
		$sql = "update MANAGE_ISSUE set STATUS ='" . $data . "' where ISSUE_ID='" . $issue_id . "' ";
		$this->db->query( $sql );
	}

	public function insertDetIssue2( $data, $id_det ) {
		$iniquery = $this->db->query( "SELECT TO_CHAR(MAX(DATE_ISSUE),'mm/dd/yyyy') AS DATE_ISSUE FROM DETAIL_ISSUE WHERE ISSUE_ID='" . $data['ISSUE_ID'] . "'" );
		$tanggal  = $iniquery->row()->DATE_ISSUE;
		$now      = time();
		$date     = date( "m/d/Y" );
		$date1    = strtotime( $tanggal );
		//$diff=date_diff($date1,$date);
		$datediff = $now - $date1;
		$diff     = floor( $datediff / ( 60 * 60 * 24 ) );
		$sql      = "INSERT INTO DETAIL_ISSUE(
    ID,
    DURATION,
    PROJECT_ID,
    ISSUE_ID,
    NOTE,
    SUBJECT,
    USER_ID,
    EVIDENCE,
    PRIORITY,
    STATUS,
    DATE_ISSUE
    )VALUES(
    '" . $id_det . "',
    " . $diff . ",
    '" . $data['PROJECT_ID'] . "',
    '" . $data['ISSUE_ID'] . "',
    '" . $data['NOTE'] . "',
    '" . $data['SUBJECT'] . "',
    '" . $data['USER_ID'] . "',
    '" . $data['EVIDENCE'] . "',
    '" . $data['PRIORITY'] . "',
    '" . $data['STATUS'] . "',
    to_date('" . $date . "','mm/dd/yy')
    )";
		$q        = $this->db->query( $sql );
	}

	public function insertDetIssue3( $data, $id_det ) {
		$iniquery = $this->db->query( "SELECT TO_CHAR(MAX(DATE_ISSUE),'mm/dd/yyyy') AS DATE_ISSUE FROM DETAIL_ISSUE WHERE ISSUE_ID='" . $data['ISSUE_ID'] . "'" );
		$tanggal  = $iniquery->row()->DATE_ISSUE;
		$now      = time();
		$date     = date( "m/d/Y" );
		$date1    = strtotime( $tanggal );
		//$diff=date_diff($date1,$date);
		$datediff = $now - $date1;
		$diff     = floor( $datediff / ( 60 * 60 * 24 ) );
		$sql      = "INSERT INTO DETAIL_ISSUE(
    ID,
    DURATION,
    PROJECT_ID,
    ISSUE_ID,
    NOTE,
    SUBJECT,
    USER_ID,
    PRIORITY,
    STATUS,
    DATE_ISSUE
    )VALUES(
    '" . $id_det . "',
    " . $diff . ",
    '" . $data['PROJECT_ID'] . "',
    '" . $data['ISSUE_ID'] . "',
    '" . $data['NOTE'] . "',
    '" . $data['SUBJECT'] . "',
    '" . $data['USER_ID'] . "',
    '" . $data['PRIORITY'] . "',
    '" . $data['STATUS'] . "',
    to_date('" . $date . "','mm/dd/yy')
    )";
		$q        = $this->db->query( $sql );
	}

	public function insertDetIssue3High( $data, $id_det ) {
		$iniquery = $this->db->query( "SELECT TO_CHAR(MAX(DATE_ISSUE),'mm/dd/yyyy') AS DATE_ISSUE FROM DETAIL_ISSUE WHERE ISSUE_ID='" . $data['ISSUE_ID'] . "'" );
		$tanggal  = $iniquery->row()->DATE_ISSUE;
		$now      = time();
		$date     = date( "m/d/Y" );
		$date1    = strtotime( $tanggal );
		//$diff=date_diff($date1,$date);
		$datediff = $now - $date1;
		$diff     = floor( $datediff / ( 60 * 60 * 24 ) );
		$sql      = "INSERT INTO DETAIL_ISSUE(
    ID,
    DURATION,
    PROJECT_ID,
    ISSUE_ID,
    NOTE,
    SUBJECT,
    USER_ID,
    PRIORITY,
    STATUS,
    DATE_ISSUE
    )VALUES(
    '" . $id_det . "',
    " . $diff . ",
    '" . $data['PROJECT_ID'] . "',
    '" . $data['ISSUE_ID'] . "',
    '" . $data['NOTE'] . "',
    '" . $data['SUBJECT'] . "',
    '" . $data['USER_ID'] . "',
    '" . $data['PRIORITY'] . "',
    '" . $data['STATUS'] . "',
    to_date('" . $date . "','mm/dd/yy')
    )";
		$q        = $this->db->query( $sql );
	}

	public function deleteIssue( $id ) {
		$this->db->delete( 'MANAGE_ISSUE', array( 'ISSUE_ID' => $id ) );
	}

	public function deleteDetIssue( $id ) {
		$this->db->delete( 'DETAIL_ISSUE', array( 'ISSUE_ID' => $id ) );
	}

	function editIssue( $data ) {

		$date = date( "m/d/Y H:i:s" );
		$this->db->set( 'ISSUE_ID', $data['ISSUE_ID'] );
		$this->db->set( 'NOTE', $data['NOTE'] );
		$this->db->set( 'PROJECT_ID', $data['PROJECT_ID'] );
		$this->db->set( 'SUBJECT', $data['SUBJECT'] );
		$this->db->set( 'USER_ID', $data['USER_ID'] );
		$this->db->set( 'EVIDENCE', $data['EVIDENCE'] );
		$this->db->set( 'PRIORITY', $data['PRIORITY'] );
		$this->db->set( 'STATUS', $data['STATUS'] );
		$this->db->set( 'DATE_ISSUE', "to_date('$date','MM/DD/YYYY HH24:MI:SS')", false );
		$this->db->where( 'ISSUE_ID', $data['ISSUE_ID'] );
		$this->db->update( 'MANAGE_ISSUE', $data );


	}

}

?>
