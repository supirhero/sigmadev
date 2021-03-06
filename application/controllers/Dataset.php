<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Dataset extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->datajson['privilege'] = [
			'master_data_access'                    => false,
			'manage_role_access'                    => false,
			'create_edit_delete_task_updatepercent' => false,
			'req_rebaseline'                        => false,
			'acc_deny_rebaseline'                   => false,
			'assign_project_member'                 => false,
			'project_report'                        => true,
			'project_activities'                    => false,
			'acc_deny_timesheet'                    => false,
			'report_overview'                       => false
		];
		$this->load->model( 'M_session' );
		$this->load->model( 'M_register' );
		$this->load->model( 'M_holiday' );
		$this->load->model( 'M_project_type' );


		//TOKEN LOGIN CHECKER
		$datauser = $this->M_session->GetDataUser();
		//    print_r($decoded_user_data);
		$this->datajson['token'] = $datauser["token"];

		if ( isset( $datauser["error"] ) ) {
			$this->output->set_status_header( $datauser["status"] );
			echo json_encode( $datauser );
			die();
		}
		$decoded_user_data = array_change_key_case( $datauser["data"], CASE_UPPER );

		//if login success
		if ( ! isset( $decoded_user_data[0] ) ) {
			//get user data from token

			//for login bypass ,this algorithm is not used
			//$this->datajson['userdata'] = (array)$decoded_user_data['data'];
			//this code below for login bypass
			$this->datajson['userdata'] = $decoded_user_data;
		} //if login fail
		else {
			echo $decoded_user_data[0];
			die();
		}


		/*================================================================================*/
		/*FOR PRIVILEGE*/
		/*===============================================================================*/
		//PRIVILEGE CHECKER
		$url_dest   = strtolower( $this->uri->segment( 1 ) . "/" . $this->uri->segment( 2 ) );
		$privilege  = $this->db->query( "select al.access_id,al.type,au.access_url,pal.privilege
                                    from access_list al
                                    join access_url au
                                    on al.access_id = au.access_id
                                    join profile_access_list pal
                                    on
                                    pal.access_id = au.access_id
                                    where pal.profile_id = " . $this->datajson['userdata']['PROF_ID'] . "
                                    order by al.type asc" )->result_array();
		$profile_id = $this->datajson['userdata']['PROF_ID'];
		foreach ( $privilege as $priv ) {
			$will_die = 0;
			//jika akses url ada di dalam db
			if ( $priv['ACCESS_URL'] == $url_dest ) {
				//jika akses tipe nya business
				if ( $priv['TYPE'] == 'BUSINESS' ) {
					if ( $priv['PRIVILEGE'] == 'all_bu' ) {
						$this->allowed_bu = "'BAS','TSC','TMS','FNB','CIB','INS','MSS','CIA','SGP','SSI','SMS'";
						$will_die         = 0;
					} elseif ( $priv['PRIVILEGE'] == 'only_bu' ) {
						//fetching busines unit
						$user_bu        = $this->datajson['userdata']['BU_ID'];
						$user_bu_parent = $this->db->query( "select bu_parent_id from p_bu where bu_id = '$user_bu'" )->row()->BU_PARENT_ID;

						$directorat_bu = [];
						//for if tolerant array_search
						$directorat_bu[] = null;
						//if company
						if ( $user_bu == 0 ) {
							$bu_id_all = $this->db->query( "select bu_id from p_bu" )->result_array();
							foreach ( $bu_id_all as $buid ) {
								$directorat_bu[] = $buid['BU_ID'];
							}
						} //if directorat
						elseif ( $user_bu_parent == 0 ) {
							$bu_id_all = $this->db->query( "select bu_id from p_bu where bu_parent_id = '$user_bu'" )->result_array();
							foreach ( $bu_id_all as $buid ) {
								$directorat_bu[] = $buid['BU_ID'];
							}
						} //if bu
						else {
							$directorat_bu[] = $this->datajson['userdata']['BU_ID'];
						}
						switch ( $priv['ACCESS_ID'] ) {
							case '1':
								if ( $this->datajson['userdata']['PROF_ID'] == 7 ) {
									$bu_id = 'masuk';
								} else {
									$bu_id = 'cant';
								}
								break;
							case '2':
								if ( $url_dest == 'project/addproject_acion' ) {
									$bu_id = $this->db->query( "select bu_id from p_bu where bu_code = '" . $_POST['BU'] . "'" )->row()->BU_ID;
								} elseif ( $url_dest == 'project/addproject_view' ) {
									$bu_id = $this->db->query( "select bu_id from p_bu where bu_code = '" . $_POST['bu_code'] . "'" )->row()->BU_ID;
								}
								break;
							case '3':
								$bu_id = $this->db->query( "select bu_id from p_bu where bu_code = '" . $_POST['bu_code'] . "'" )->row()->BU_ID;
								break;
							case '4':
								$bu_id = $this->input->post( 'BU_ID' );
								break;
							case '5':
								$this->allowed_bu = "'" . $this->db->query( "select bu_code from p_bu where bu_id = '$user_bu'" )->row()->BU_CODE . "'";
								$bu_id            = 'masuk';
								break;
							case '6':
								$bu_id = $_POST['bu'];
								break;
							case '7':
								$bu_id = $_POST['BU_ID'];
								break;
							case '8' :
								if ( $this->datajson['userdata']['PROF_ID'] == 3 || $this->datajson['userdata']['PROF_ID'] == 7 ) {
									$bu_id = 'masuk';
								}
								break;
							case '16':
								$bu_id = $this->db->query( "select pbu.bu_id from projects p 
                                                           join p_bu pbu
                                                           on pbu.bu_code = p.bu_code
                                                           where p.project_id = '" . $this->input->post( 'project_id' ) . "'
                                                           " )->row()->BU_ID;
								break;
						}
						if ( ! ( ( array_search( $bu_id, $directorat_bu ) != null || $bu_id == 'masuk' ) && $bu_id != null ) ) {
							$will_die = 1;
						} else {
							$will_die = 0;
						}
					} else {
						$will_die = 1;
					}
					if ( $will_die == 1 ) {
						$user_bu_name  = $this->db->query( "select bu_name from p_bu where bu_id = '" . $this->datajson['userdata']['BU_ID'] . "'" )->row()->BU_NAME;
						$acces_bu_name = $this->db->query( "select bu_name from p_bu where bu_id = '" . $bu_id . "'" )->row()->BU_NAME;
						$this->output->set_status_header( 403 );
						$returndata['status']  = 'failed';
						$returndata['message'] = "Anda tidak bisa mengakses feature yang ada di business unit ini. Business unit anda : '$user_bu_name' dan business unit yang anda akan akses : '$acces_bu_name'";
						echo json_encode( $returndata );
						die;
					}

				} //jika akses tipe nya project
				elseif ( $priv['TYPE'] == 'PROJECT' ) {
					//fetching granted project list
					$granted_project        = $this->db->query( "SELECT   distinct project_id
                                                           FROM (SELECT a.user_id, a.user_name, c.project_id, c.project_name, c.bu_code, z.bu_name,
                                                                        c.project_complete, c.project_status, c.project_desc,
                                                                        c.created_by
                                                                   FROM USERS a INNER JOIN resource_pool b ON a.user_id = b.user_id
                                                                        INNER JOIN projects c ON b.project_id = c.project_id
                                                                        INNER JOIN p_bu z on c.bu_code = z.bu_code
                                                                 UNION
                                                                 SELECT a.user_id, a.user_name, b.project_id, b.project_name, b.bu_code, z.bu_name,
                                                                        b.project_complete, b.project_status, b.project_desc,
                                                                        b.created_by
                                                                   FROM USERS a INNER JOIN projects b ON a.user_id = b.created_by
                                                                   INNER JOIN p_bu z on b.bu_code = z.bu_code
                                                                        )
                                                                        where user_id='" . $this->datajson['userdata']['USER_ID'] . "' or created_by='" . $this->datajson['userdata']['USER_ID'] . "'" )->result_array();
					$granted_project_list   = [];
					$granted_project_list[] = null;

					//rearrange project list so it can readable to array search
					foreach ( $granted_project as $gp ) {
						$granted_project_list[] = $gp['PROJECT_ID'];
					}

					if ( $priv['PRIVILEGE'] == 'can' ) {
						//get project id
						switch ( $priv['ACCESS_ID'] ) {
							case '9':
								$project_id_req = $_POST['PROJECT_ID'];
								break;
							case '10':
								$project_id_req = $_POST['project_id'];
								break;
							case '11':
								switch ( $url_dest ) {
									case 'task/upload_wbs':
										$project_id_req = $_POST['project_id'];
										break;
									case 'task/assigntaskmemberproject':
										$project_id     = explode( ".", $_POST['WBS_ID'] );
										$project_id_req = $project_id[0];
										break;
									case 'task/removetaskmemberproject':
										$project_id     = explode( ".", $_POST['WBS_ID'] );
										$project_id_req = $project_id[0];
										break;
									case 'task/createtask':
										$project_id_req = $this->input->post( "PROJECT_ID" );
										break;
									case 'task/edittaskpercent':
										$project_id_req = $this->input->post( "PROJECT_ID" );
										break;
									case 'task/edittask_action':
										$project_id_req = $this->input->post( "project_id" );
										break;
									case 'task/deletetask':
										$id             = $_POST['wbs_id'];
										$project_id_req = $this->M_detail_project->getProjectTask( $id );
										break;
									case ( $url_dest == 'project/rebaseline' ) || ( $url_dest == 'project/baseline' ):
										$user_id = $this->datajson['userdata']['USER_ID'];
										$gpl     = $this->db->query( "select project_id from projects where pm_id ='$user_id'" )->result_array();

										$granted_project_list = null;
										$granted_project_list = [];
										foreach ( $gpl as $gg ) {
											$granted_project_list[] = $gg['PROJECT_ID'];
										}
										$project_id_req = $this->input->post( "project_id" );
										break;
								}
								break;
							case '12':
								$id             = $_POST['MEMBER'];
								$project_id_req = $this->M_detail_project->getRPProject( $id );
								break;
							case '13':
								$project_id_req = $this->uri->segment( 3 );
								break;
							case '14':
								$project_id_req = $this->input->post( "PROJECT_ID" );
								break;
							case '15':
								$project_id_req = $this->input->post( "PROJECT_ID" );
								break;
						}

						if ( ! in_array( $project_id_req, $granted_project_list ) ) {
							$will_die = 1;
						}
					} else {
						$will_die = 1;
					}
					if ( $will_die == 1 ) {
						$this->output->set_status_header( 403 );
						$returndata['status']  = 'failed';
						$returndata['message'] = 'Anda tidak bisa mengakses feature ini';
						echo json_encode( $returndata );
						die;
					}
				} else {
					$will_die = 1;
				}
				if ( $will_die == 1 ) {
					$this->output->set_status_header( 403 );
					$returndata['status']  = 'failed';
					$returndata['message'] = 'Anda tidak bisa mengakses feature ini';
					echo json_encode( $returndata );
					die;
				}
			}
		}
		/*===============================================================================*/
	}


	function index() {
		$data['users']['user_int'] = ( $this->M_register->tampil_int() );
		$data['users']['user_ext'] = ( $this->M_register->tampil_eks() );
		$data['customers']         = json_decode( file_get_contents( 'http://180.250.18.227/api/index.php/mis/customer' ) );
		$data['partners']          = json_decode( file_get_contents( 'http://180.250.18.227/api/index.php/mis/vendor' ) );
		$data['holiday']           = ( $this->M_holiday->selectHoliday() );
		$data['project_type']      = $this->M_project_type->selectProjectType();

		echo json_encode( $data );
	}
}