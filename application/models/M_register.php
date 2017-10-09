<?php

class M_register extends CI_Model {

	function _construct() {
		parent::_construct();
		$this->load->database();
	}

	function register_getall() {
		$query = $this->db->get( 'USERS' );

		return $query->result();
	}

	public function buat_akun() {
		$data_baru   = array(
			'user_id'   => $this->input->post( 'user_id' ),
			'user_name' => $this->input->post( 'user_name' ),
			'email'     => $this->input->post( 'email' ),
			'password'  => mysql_real_escape_string( $_POST['pass'] ),
			'password'  => md5( 'password' )
		);
		$simpan_data = $this->db->insert( $this->users, $data_baru );

		return $simpan_data;
	}

	function add_account( $data ) {
		$this->load->database();
		$this->db->insert( 'users', $data );

		//return  mysql_insert_nik();
	}

	public function tampil_int() {
		//$result=array();
		//$sql = 'select * from users ';
		//$query = $this->db->query($sql);
		//if($query->num_rows > 0){
		//	$result=$query->result();
		//	return $result;
		//}else{
		//	return 'kosong';
		//}
		$q1 = $this->db->query( "select c.*,bu_name, prof_name from users c inner join profile b on c.prof_id = b.prof_id inner join p_bu d on c.bu_id=d.bu_id  where user_type_id='int'" );

		return $q1->result();

	}

	public function tampil_eks() {

		$q2 = $this->db->query( "select * from users where user_type_id='ext'" );

		return $q2->result();

	}

	function caridata() {
		$c = $this->input->POST( 'cari' );
		$this->db->like( 'user_name', $c );
		$query = $this->db->get( 'users' );

		return $query->result();
	}

	function hapus( $user_name ) {
		$where = array( 'user_name' => $user_name );
		$this->m_data->hapus_data( $where, 'User Name' );
		redirect( 'home' );
	}


	function tambahuser( $dataarray ) {
		for ( $i = 1; $i < count( $dataarray ); $i ++ ) {
			$getBU  = $this->db->query( "SELECT BU_ID FROM P_BU where BU_NAME like '" . $dataarray[ $i ]['BU_ID'] . "'" );
			$hasil2 = $getBU->row();
			//$getProfile = $this->db->query("SELECT PROF_ID FROM PROFILE where PROF_NAME like '".$dataarray[$i]['PROF_ID']. "'");
			//$hasil3 = $getProfile->row();
			$data = array(
				'USER_ID'      => $dataarray[ $i ]['USER_ID'],
				'PROF_ID'      => $dataarray[ $i ]['PROF_ID'],
				'USER_NAME'    => $dataarray[ $i ]['USER_NAME'],
				'EMP_CAT'      => $dataarray[ $i ]['EMP_CAT'],
				'POSITION'     => $dataarray[ $i ]['POSITION'],
				'BU_ID'        => $hasil2->BU_ID,
				'EMAIL'        => $dataarray[ $i ]['EMAIL'],
				'SUP_ID'       => $dataarray[ $i ]['SUP_ID'],
				'USER_TYPE_ID' => 'int',
				'IS_ACTIVE'    => '1',
				//'IS_ACTIVE'=>$dataarray[$i]['IS_ACTIVE'],
				//'PHONE_NO'=>$dataarray[$i]['PHONE_NO'],
				//  'PASSWORD'=>$dataarray[$i]['PASSWORD']
				//'ADDRESS'=>$dataarray[$i]['ADDRESS'],
				//'IS_SHIFT'=>$dataarray[$i]['IS_SHIFT'],
				//'IMAGE'=>$dataarray[$i]['IMAGE'],
			);

			$get   = $this->db->query( "SELECT * FROM users where EMAIL like '%" . $dataarray[ $i ]['EMAIL'] . "%'" );
			$hasil = $get->row();
			if ( isset( $hasil->EMAIL ) ) {
				if ( $dataarray[ $i ]['USER_ID'] != $hasil->USER_ID ) {
					$updaterp = $this->db->query( "update resource_pool set USER_ID='" . $dataarray[ $i ]['USER_ID'] . "' where USER_ID like '%" . $hasil->USER_ID . "%'" );
				}
				$this->db->where( 'EMAIL', $dataarray[ $i ]['EMAIL'] );
				$this->db->update( 'USERS', $data );
			} else {
				$this->db->insert( 'USERS', $data );
			}
		}
	}

	function deleteuser( $dataarray ) {
		for ( $i = 0; $i < count( $dataarray ); $i ++ ) {
			$data  = array(
				'USER_ID'      => $dataarray[ $i ]['USER_ID'],
				'PROF_ID'      => $dataarray[ $i ]['PROF_ID'],
				'BU_ID'        => $dataarray[ $i ]['BU_ID'],
				'SUP_ID'       => $dataarray[ $i ]['SUP_ID'],
				'USER_TYPE_ID' => $dataarray[ $i ]['USER_TYPE_ID'],
				'USER_NAME'    => $dataarray[ $i ]['USER_NAME'],
				'EMAIL'        => $dataarray[ $i ]['EMAIL'],
				'PHONE_NO'     => $dataarray[ $i ]['PHONE_NO'],
				'IS_ACTIVE'    => $dataarray[ $i ]['IS_ACTIVE'],
				'LAST_LOGIN'   => $dataarray[ $i ]['LAST_LOGIN'],
				'PASSWORD'     => $dataarray[ $i ]['PASSWORD'],
				'ADDRESS'      => $dataarray[ $i ]['ADDRESS'],
				'IS_SHIFT'     => $dataarray[ $i ]['IS_SHIFT'],
				'IMAGE'        => $dataarray[ $i ]['IMAGE']

			);
			$get   = $this->db->query( "SELECT * FROM users where USER_ID='" . $dataarray[ $i ]['USER_ID'] . "'" );
			$hasil = $get->row();
			if ( isset( $hasil->USER_ID ) ) {
				$this->db->where( 'USER_ID', $dataarray[ $i ]['USER_ID'] );
				$this->db->update( 'USERS', $data );
			} else {
				$this->db->insert( 'USERS', $data );
			}
		}
	}


}

?>
