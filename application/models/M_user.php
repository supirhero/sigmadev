<?php
Class M_user extends CI_Model{
    public function ViewDataUser()
    {
        $data['USERS'] = $this->m_data->tampil_data()->result();
        $this->load->view('v_tampil',$data);
    }

    function GetDataUser($user_id){
        //return $this->db->get_where($table,$where);
        $result=array();
        $sql="select * from USERS where USER_ID='".$user_id."'";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->result();
        }
        return $result;
    }

    function GetOldPass($password){
        $this->db->where("PASSWORD",$password);
        $query=$this->db->get('USERS');
    }


    public function UpdateDataImage($user_id,$image){
        $sql="update USERS set IMAGE='".$image."' where USER_ID='".$user_id."'";
        $q = $this->db->query($sql);
    }

    public function UpdateDataUser($user_id,$user_name,$address,$email,$phone_no){
        $sql="update USERS set USER_NAME='".$user_name."', ADDRESS='".$address."', EMAIL='".$email."', PHONE_NO='".$phone_no."' where USER_ID='".$user_id."'";
        $q = $this->db->query($sql);
        //$pesan = "";
        //if($q){
        //	redirect('/User/berhasil');
        //}else{
        //	redirect('/User/gagal');
        //}
        //$response = array('pesan'=>$pesan, 'data'=>$_POST);
        //echo json_encode($response);
        //exit;
    }

    public function setPassword($user_id, $password) {
        $sql = "update USERS set PASSWORD='".(md5($password))."' WHERE USER_ID='".$user_id."'";
        $q = $this->db->query($sql);
    }

    function lastLogin($user_id){
        $delivDate = date('d-m-Y');
        $sql="UPDATE USERS SET LAST_LOGIN=to_date('".$delivDate."','dd-mm-yy') WHERE USER_ID='".$user_id."'";
        $q = $this->db->query($sql);
    }

    public function DeleteDataUser($user_id,$email){
        $sql = "delete from USERS where USER_ID='".$user_id."'" ;
        $sql2 = "delete from VERIFICATION where EMAIL='".$email."' ";
        $q = $this->db->query($sql);
        $q2 = $this->db->query($sql2);


    }


    function createIdentifier($email){

        $data=array(
            'EMAIL'=>$email,
            'IDENTIFIER'=>md5($email),
            'IS_VALID'=>'1'
        );
        $this->db->insert('VERIFICATION',$data);


    }
    function getName($email){
        $sql="select USER_NAME from USERS where EMAIL='".$email."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->USER_NAME;
            return $result;
        }
    }

    function getNameVendor($emailv){
        $sql="select USER_NAME from USERS where EMAIL='".$emailv."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->USER_NAME;
            return $result;
        }
    }
    function getCurrPassword($user_id){
        $sql="select PASSWORD from USERS where USER_ID='".$user_id."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->PASSWORD;
            return $result;
        }
    }

    function getEmail($user_id){
        $sql="select EMAIL from USERS where USER_ID='".$user_id."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->EMAIL;
            return $result;
        }
    }


    function getSupID($user_id){
        $sql="select SUP_ID from USERS where USER_ID='".$user_id."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->SUP_ID;
            return $result;
        }
    }


    function getEmailSupID($sup_id){
        $sql="select EMAIL from USERS where USER_ID='".$sup_id."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->EMAIL;
            return $result;
        }
    }


    function deactivateUser($email){
        $sql="update USERS set IS_ACTIVE='0' where EMAIL='".$email."'";
        $q = $this->db->query($sql);
    }

    function statusActive($user_id){
        $sql="UPDATE USERS SET IS_ACTIVE='1' WHERE USER_ID='".$user_id."'";
        $q = $this->db->query($sql);
    }

    function deleteIdentifier($email){
        $this->db->where('EMAIL',$email);
        $this->db->delete('VERIFICATION');
    }

}
?>
