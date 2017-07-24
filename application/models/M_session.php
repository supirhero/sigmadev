<?php
Class M_session extends CI_Model{
    function GetDataUser($token){
        //return $this->db->get_where($table,$where);
        $result=array();
        $sql="select * from USER_SESSION WHERE SESSION_TOKEN='$token'";

        $sql="SELECT * FROM USER_SESSION INNER JOIN USERS ON USER_SESSION.USER_ID = USERS.USER_ID WHERE USER_SESSION.SESSION_TOKEN='$token'";
      //  $sql="SELECT column_name FROM user_tab_cols WHERE table_name = 'USER_SESSION' ";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            $result = $q->row_array();
        }
        return $result;
    }

    public function insert_session($user_id){
$new_session = md5(time().rand(1,999999));
        $this->db->set('SESSION_ID',1);
        $this->db->set('SESSION_TOKEN',$new_session);
        $this->db->set('USER_ID',$user_id);
        $this->db->set('SESSION_EXPIRED',time()+7*24*60*60);
        $this->db->insert("USER_SESSION");
return $new_session;
    }

}
?>
