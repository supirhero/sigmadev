<?php

Class M_project_type extends CI_Model {
	function selectProjectType( $keyword = null ) {
		$query = $this->db->query( 'SELECT ID, PROJECT_TYPE, CATEGORY AS TYPE_EFFORT FROM P_PROJECT_CATEGORY' );
		$hasil = $query->result_array();

		return $hasil;

	}

	/*  function editMenu($data){
	$this->db->where('id', $id);
	$this->db->update('mytable', $data);
  }*/
	function getMaxType() {
		return $this->db->query( "select nvl(max(PROJECT_TYPE_ID)+1, 8000001) as NEW_ID from P_PROJECT_TYPE" )->row()->NEW_ID;
	}

	public function insertProjectType( $data ) {
//  $id = $this->M_p_project_type->getMaxType();
		$this->db->insert( "P_PROJECT_TYPE", $data );


	}


	public function deleteProjectType( $id ) {
		$this->db->delete( 'P_PROJECT_TYPE', array( 'PROJECT_TYPE_ID' => $id ) );
	}

	function editProjectType( $data ) {
		$this->db->where( 'PROJECT_TYPE_ID', $data['PROJECT_TYPE_ID'] );
		$this->db->update( 'P_PROJECT_TYPE', $data );

	}

	public function validateUser( $params ) {
		switch ( $params ) {
			case 'PROJECT_TYPE':
				$value = $this->input->post( 'USER_ID' );
				break;
			case 'EMAIL':
				$value = $this->input->post( 'EMAIL' );
				break;

		}
		$check;
		$sql = "select * from P_PROJECT_TYPE where " . $params . "='" . $value . "'";
		$q   = $this->db->query( $sql );

		if ( $q->num_rows() > 0 ) {
			$check = true;
		} else {
			$check = false;
		}

		return $check;
	}


}

?>
