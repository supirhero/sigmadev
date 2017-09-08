<?php
Class M_session extends CI_Model{
    function GetDataUser(){
        $token = (isset($_SERVER['HTTP_TOKEN']))?$_SERVER['HTTP_TOKEN']:(isset($_GET['token'])?$_GET['token']:false);
        $result["token"]= $token;

        if(!$token)
        {
            $result["error"]= "Token required(must login first)";
            $result["status"]= 401;
        }
        else{
            $result=array();

        $sql="SELECT * FROM USER_SESSION INNER JOIN USERS ON USER_SESSION.USER_ID = USERS.USER_ID WHERE USER_SESSION.SESSION_TOKEN='$token'";
        $q = $this->db->query($sql);

        if($q->num_rows() > 0){
            if($result["data"]["SESSION_EXPIRED"] <= time())
            {
                $result["error"]= "Your token is expired";
                $result["status"]= 401;

            }
            else{
                $this->update_session($token);
                $result["status"]= 200;
                $result["data"] = $q->row_array();
            }
        }
        else{
            $result["status"]= 401;
            $result["error"]= "Your token is invalid or expired";
        }
        }


            return $result;
    }

    public function insert_session($user_id){
$new_session = md5(time().rand(1,999999));
        $this->db->set('SESSION_ID',1);
        $this->db->set('SESSION_TOKEN',$new_session);
        $this->db->set('USER_ID',$user_id);
        $this->db->set('SESSION_EXPIRED',time()+5*30*60);
        $this->db->insert("USER_SESSION");
return $new_session;
    }
    public function update_session($token){
        $newexpiry=time()+30*60;
        $sql="update USER_SESSION set SESSION_EXPIRED='".$newexpiry."' where SESSION_TOKEN='".$token."'";
        $q = $this->db->query($sql);
    }

}
?>
