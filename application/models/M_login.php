<?php
Class M_login extends CI_Model
{
    function loginsso($user_id)
    {

        $this->db->where("USER_ID",$user_id);
        //$this->db->where("PASSWORD",$password);
        $this->db->where("IS_ACTIVE",1);
        $query=$this->db->get('USERS');

        if($query->num_rows()>0)
        {
            foreach($query->result() as $rows)
            {
                //add all data to session
                $newdata = array(
                    'USER_ID'  => $rows->USER_ID,
                    'USER_NAME'  => $rows->USER_NAME,
                    'EMAIL'    => $rows->EMAIL,
                    'BU_ID' => $rows->BU_ID,
                    'USER_TYPE_ID' => $rows->USER_TYPE_ID,
                    'SUP_ID' => $rows->SUP_ID,
                    'PROF_ID' => $rows->PROF_ID,
                    'LAST_LOGIN' => $rows->LAST_LOGIN,
                    'PASSWORD' => $rows->PASSWORD,
                    'logged_in'  => TRUE,
                );
            }
            $this->session->set_userdata($newdata);
            return true;
        }
        //return $query->result();
        return false;

    }
    function login($user_id, $password)
    {
        /*
           //  $this->db->where("USER_ID",$user_id);
           $this->db->where("PASSWORD",$password);
           //$this->db->where("USER_TYPE_ID",'ext');
           $this->db->where("IS_ACTIVE",1);
           $this->db->where("USER_ID = '".$user_id."' OR EMAIL = '".$user_id."'", NULL, FALSE);
        */
        $Q = "SELECT * FROM USERS WHERE (USER_ID = '".$user_id."' OR EMAIL = '".$user_id."') AND PASSWORD = '".$password."' AND IS_ACTIVE = '1'";

        $query=$this->db->query($Q);

        if($query->num_rows()>0)
        {
            foreach($query->result() as $rows)
            {
                //add all data to session
                $newdata = array(
                    'USER_ID'  => $rows->USER_ID,
                    'USER_NAME'  => $rows->USER_NAME,
                    'EMAIL'    => $rows->EMAIL,
                    'BU_ID' => $rows->BU_ID,
                    'USER_TYPE_ID' => $rows->USER_TYPE_ID,
                    'SUP_ID' => $rows->SUP_ID,
                    'PROF_ID' => $rows->PROF_ID,
                    'LAST_LOGIN' => $rows->LAST_LOGIN,
                    'PASSWORD' => $rows->PASSWORD,
                    'logged_in'  => TRUE,
                );
            }
            $this->session->set_userdata($newdata);
            return true;
        }
        return false;

    }
    function getSupBU($email){
        return $this->db->query("select BU_ID from USERS where EMAIL='".$email."' and ROWNUM <=1 ")->row()->BU_ID;
    }
    function validateLogin($user_id, $password){
        $sql1="select * from USERS where USER_ID='".$user_id."'";
        $sql2="select * from USERS where USER_ID='".$user_id."' and PASSWORD='".$password."'";
        $sql3="select * from USERS where USER_ID='".$user_id."' and PASSWORD='".$password."' and IS_ACTIVE=1";

        $q1 = $this->db->query($sql1);
        $q2 = $this->db->query($sql2);
        $q3 = $this->db->query($sql3);

        if($q1->num_rows()== 0){
            $error="err1";
        }elseif($q2->num_rows() == 0){
            $error="err2";
        }elseif($q3->num_rows() == 0){
            $error="err3";
        }else{
            $error="0";
        }
        return $error;

    }

    function getName($email){
        $sql="select USER_NAME from USERS where EMAIL='".$email."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->USER_NAME;
            return $result;
        }
    }
    function getNIK($email){
        $sql="select USER_ID from USERS where EMAIL='".$email."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->USER_ID;
            return $result;
        }
    }

    function getID($email){
        $sql="select USER_ID from USERS where EMAIL='".$email."' and ROWNUM <=1 ";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            $result=$q->row()->USER_ID;
            return $result;
        }

    }
    public function add_user()
    {
        $type_work=$this->input->post('VENDOR');
        if(!empty($type_work)){
            $type="ext";
            $user=$this->input->post('VENDOR');
        }else{
            $type="int";
            $user=$this->input->post('USER_ID');
        }
        $data=array(
            'USER_ID'=>$user,
            'EMAIL'=>$this->input->post('EMAIL'),
            'USER_NAME'=>$this->input->post('USER_NAME'),
            'PASSWORD'=>md5($this->input->post('PASSWORD')),
            'PROF_ID'=>'6',
            'IS_ACTIVE'=>'0',
            'BU_ID'=>'0',
            'USER_TYPE_ID'=>$type
        );

        $this->db->insert('USERS',$data);
        if(!empty($type_work)){
            $e_vendor=array(
                'USER_ID'=>$this->input->post('USER_ID'),
                'EMAIL_VENDOR'=>$this->input->post('VENDOR'),
            );
            $this->db->insert('VENDOR',$e_vendor);
        }

    }
    public function addUserVendor(){

        $emailSup=$this->input->post('V_EMAIL_SUP');
        $bu=$this->getSupBU($emailSup);
        $nik=$this->getID($emailSup);
        $data=array(
            'USER_ID'=>$this->input->post('V_USER_ID'),
            'EMAIL'=>$this->input->post('V_EMAIL'),
            'USER_NAME'=>$this->input->post('V_USER_NAME'),
            'PASSWORD'=>md5($this->input->post('V_PASSWORD')),
            'PROF_ID'=>'6',
            'IS_ACTIVE'=>'0',
            'SUP_ID'=>$nik,
            'BU_ID'=>$bu,
            'USER_TYPE_ID'=>'ext'
        );
        $this->db->insert('USERS',$data);
    }
    public function tampil()
    {
        $q=$this->db->get('USERS');
        return $q->result();
    }
    public function validateUser($params){
        switch($params){
            case 'USER_ID':
                $value=$this->input->post('USER_ID');
                break;
            case 'EMAIL':
                $value=$this->input->post('EMAIL');
                break;
            case 'V_EMAIL':
                $value=$this->input->post('V_EMAIL');
                $params="EMAIL";
                break;
            case 'V_EMAIL_SUP':
                $value=$this->input->post('V_EMAIL_SUP');
                $params="EMAIL";
                break;
            case 'V_USER_ID':
                $value=$this->input->post('V_EMAIL_SUP');
                $params="USER_ID";
                break;
        }
        $check;
        //$sql="select * from users where ".$params."='".$this->db->escape($value)."'";
        $sql="select * from users where ".$params."='".$value."'";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $check=TRUE;
        }else{
            $check=FALSE;
        }

        return $check;
    }

    public function recordVerification(){

        $data=array(
            'EMAIL'=>$this->input->post('EMAIL'),
            'IDENTIFIER'=>md5($this->input->post('EMAIL')),
            'IS_VALID'=>'1'
        );
        $this->db->insert('VERIFICATION',$data);
    }
    public function recordVerificationV(){

        $data=array(
            'EMAIL'=>$this->input->post('V_EMAIL'),
            'IDENTIFIER'=>md5($this->input->post('V_EMAIL')),
            'IS_VALID'=>'1'
        );
        $this->db->insert('VERIFICATION',$data);
    }
    function activateRegister($email){
        $result;
        $sql="select EMAIL from VERIFICATION where IDENTIFIER='".$email."' and IS_VALID='1' ";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result=$q->row()->EMAIL;
            $sql2="update VERIFICATION set IS_VALID='0' where IDENTIFIER='".$email."'";
            $q2 = $this->db->query($sql2);
            $sql3="update USERS set IS_ACTIVE='1' where EMAIL='".$result."'";
            $q3 = $this->db->query($sql3);
            return 1;

        }else{
            return 0;
        }


    }

    public function recordGetPassword(){

        $data=array(
            'EMAIL'=>$this->input->post('EMAIL'),
            'IDENTIFIER'=>md5($this->input->post('EMAIL')),
            'IS_VALID'=>'1'
        );
        $this->db->insert('RESET_PASS',$data);
    }
    function activateGetPassword($email){
        $result;
        $sql="select EMAIL from RESET_PASS where IDENTIFIER='".$email."' and IS_VALID='1' ";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result=$q->row()->EMAIL;
            $sql2="update RESET_PASS set IS_VALID='0' where IDENTIFIER='".$email."'";
            $q2 = $this->db->query($sql2);
            $sql3="update USERS set PASSWORD='1' where EMAIL='".$result."'";
            $q3 = $this->db->query($sql3);
            return $result;

        }else{
            return 0;
        }


    }

    function validateForgot($email){

        $sql1="select * from USERS where EMAIL='".$email."'";

        $q1 = $this->db->query($sql1);

        if($q1->num_rows()== 0){
            $error="err1";
        }else{
            $error="0";
        }
        return $error;

    }

    function updatePassword($email,$pass){
        $sql="UPDATE USERS SET PASSWORD='".$pass."' WHERE EMAIL='".$email."'";
        $q1 = $this->db->query($sql);
    }
}
?>
