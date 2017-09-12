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
    function GetProfile($profile_id){
        //return $this->db->get_where($table,$where);
        $result=array();
        $sql="select * from profile where PROF_ID='".$profile_id."'";
        $q = $this->db->query($sql);
$result = $q->result_array();
        return $result["PROF_NAME"];
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
        $delivDate = date('d-m-Y h:i:s');
        $sql="UPDATE USERS SET LAST_LOGIN=to_date('".$delivDate."','dd-mm-yy hh:mi:ss') WHERE USER_ID='".$user_id."'";
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
    public function userList($start=0,$end=20,$keyword=null){
      $sql ="SELECT * FROM
(select u.*,b.bu_name,
ROW_NUMBER() OVER (ORDER BY b.bu_name) Row_Num
 from users u
join p_bu b on u.bu_id=b.bu_id ";
      if ($keyword!=null) {
        $keyword=strtolower($keyword);
        $sql.=" where ";
        $sql.=" lower(user_name) like '%".$keyword."%' or";
        $sql.=" lower(user_id) like '%".$keyword."%' or";
        $sql.=" lower(email) like '%".$keyword."%' or";
        $sql.=" lower(bu_name) like '%".$keyword."%'";
      }
      $sql .=") WHERE Row_Num BETWEEN $start and $end";
      $res=$this->db->query($sql);
      return $res->result_array();
    }
    public function user_List($keyword=null){
      $sql ="SELECT * FROM
(select u.*,b.bu_name,
ROW_NUMBER() OVER (ORDER BY b.bu_name) Row_Num
 from users u
join p_bu b on u.bu_id=b.bu_id ";
      if ($keyword!=null) {
        $keyword=strtolower($keyword);
        $sql.=" where ";
        $sql.=" lower(user_name) like '%".$keyword."%' or";
        $sql.=" lower(user_id) like '%".$keyword."%' or";
        $sql.=" lower(email) like '%".$keyword."%' or";
        $sql.=" lower(bu_name) like '%".$keyword."%'";
      }
      $res=$this->db->query($sql.")");
      return $res->result();
    }
    function sendVerification($email,$name){
      $this->load->library('email');
      $config['protocol']='smtp';
      $config['smtp_host']='smtp.sigma.co.id';
      $config['smtp_user']=SMTP_AUTH_USR;
      $config['smtp_pass']=SMTP_AUTH_PWD;
      $config['smtp_port']='587';
      $config['smtp_timeout']='100';
      $config['charset']    = 'utf-8';
      $config['newline']    = "\r\n";
      $config['mailtype'] = 'html';
      $config['validation'] = TRUE;
      $this->email->initialize($config);
      $this->email->from('prouds.support@sigma.co.id', 'Project & Resources Development System');
      $this->email->to($email);
      $logo=base_url()."asset/image/logo_new_sigma1.png";
      $css=base_url()."asset/css/confirm.css";
      $this->email->attach($logo);
      $this->email->attach($css);
      $cid_logo = $this->email->attachment_cid($logo);
      $this->email->subject('Verification PROUDS Account');
      $this->email->message("<!DOCTYPE html>
      <html>
      <head>
      <meta name='viewport' content='width=device-width' />
      <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
      <title>Account Activation</title>

      <style>
      /* -------------------------------------
      GLOBAL
      ------------------------------------- */
      * {
        margin:0;
        padding:0;
      }
      * { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; }

      img {
        max-width: 100%;
      }
      .collapse {
        margin:0;
        padding:0;
      }
      body {
        -webkit-font-smoothing:antialiased;
        -webkit-text-size-adjust:none;
        width: 100%!important;
        height: 100%;
      }


      /* -------------------------------------
      ELEMENTS
      ------------------------------------- */
      a { color: #2BA6CB;}

      .btn {
        text-decoration:none;
        color:#FFF;
        background-color: #1da1db;
        width:80%;
        padding:15px 10%;
        font-weight:bold;
        text-align:center;
        cursor:pointer;
        display:inline-block;
        border-radius: 5px;
        box-shadow: 3px 3px 3px 1px #EBEBEB;
      }

      p.callout {
        padding:15px;
        text-align:center;
        background-color:#ECF8FF;
        margin-bottom: 15px;
      }
      .callout a {
        font-weight:bold;
        color: #2BA6CB;
      }

      .column table { width:100%;}
      .column {
        width: 300px;
        float:left;
      }
      .column tr td { padding: 15px; }
      .column-wrap {
        padding:0!important;
        margin:0 auto;
        max-width:600px!important;
      }
      .columns .column {
        width: 280px;
        min-width: 279px;
        float:left;
      }
      table.columns, table.column, .columns .column tr, .columns .column td {
        padding:0;
        margin:0;
        border:0;
        border-collapse:collapse;
      }

      /* -------------------------------------
      HEADER
      ------------------------------------- */
      table.head-wrap { width: 100%;}

      .header.container table td.logo { padding: 15px; }
      .header.container table td.label { padding: 15px; padding-left:0px;}


      /* -------------------------------------
      BODY
      ------------------------------------- */
      table.body-wrap { width: 100%;}


      /* -------------------------------------
      FOOTER
      ------------------------------------- */
      table.footer-wrap { width: 100%;	clear:both!important;
      }
      .footer-wrap .container td.content  p { border-top: 1px solid rgb(215,215,215); padding-top:15px;}
      .footer-wrap .container td.content p {
        font-size:10px;
        font-weight: bold;

      }


      /* -------------------------------------
      TYPOGRAPHY
      ------------------------------------- */
      h1,h2,h3,h4,h5,h6 {
        font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; line-height: 1.1; margin-bottom:15px; color:#000;
      }
      h1 small, h2 small, h3 small, h4 small, h5 small, h6 small { font-size: 60%; color: #6f6f6f; line-height: 0; text-transform: none; }

      h1 { font-weight:200; font-size: 44px;}
      h2 { font-weight:200; font-size: 37px;}
      h3 { font-weight:500; font-size: 27px;}
      h4 { font-weight:500; font-size: 23px;}
      h5 { font-weight:900; font-size: 17px;}
      h6 { font-weight:900; font-size: 14px; text-transform: uppercase; color:#444;}

      .collapse { margin:0!important;}

      p, ul {
        margin-bottom: 10px;
        font-weight: normal;
        font-size:14px;
        line-height:1.6;
      }
      p.lead { font-size:17px; }
      p.last { margin-bottom:0px;}

      ul li {
        margin-left:5px;
        list-style-position: inside;
      }

      hr {
        border: 0;
        height: 0;
        border-top: 1px dotted rgba(0, 0, 0, 0.1);
        border-bottom: 1px dotted rgba(255, 255, 255, 0.3);
      }


      /* -------------------------------------
      Shopify
      ------------------------------------- */

      .products {
        width:100%;
        height:40px;padding
        margin:10px 0 10px 0;
      }
      .products img {
        float:left;
        height:40px;
        width:auto;
        margin-right:20px;
      }
      .products span {
        font-size:17px;
      }


      /* ---------------------------------------------------
      RESPONSIVENESS
      Nuke it from orbit. It's the only way to be sure.
      ------------------------------------------------------ */

      /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
      .container {
        display:block!important;
        max-width:600px!important;
        margin:0 auto!important; /* makes it centered */
        clear:both!important;
      }

      /* This should also be a block element, so that it will fill 100% of the .container */
      .content {
        padding: 15px 15px 0 15px;
        max-width:600px;
        margin:0 auto;
        display:block;
      }

      /* Let's make sure tables in the content area are 100% wide */
      .content table { width: 100%; }

      /* Be sure to place a .clear element after each set of columns, just to be safe */
      .clear { display: block; clear: both; }


      /* -------------------------------------------
      PHONE
      For clients that support media queries.
      Nothing fancy.
      -------------------------------------------- */
      @media only screen and (max-width: 600px) {

        a[class='btn'] { display:block!important; margin-bottom:10px!important; background-image:none!important; margin-right:0!important;}

        div[class='column'] { width: auto!important; float:none!important;}

        table.social div[class='column'] {
          width:auto!important;
        }

      }

      </style>
      </head>

      <body bgcolor='#FFFFFF'>\
      <table class='head-wrap' bgcolor='#FFFFFF'>
      <tr>
      <td></td>
      <td class='header container'>

      <div class='content'>
      <table bgcolor='#FFFFFF'>
      <tr>
      <td>

      </td>

      </tr>
      </table>
      </div>

      </td>
      <td></td>
      </tr>
      </table>
      <table class='body-wrap'>
      <tr>
      <td></td>
      <td class='container' bgcolor='#FFFFFF'>

      <div class='content'>
      <table>
      <tr>
      <td align='center'>
      </td>
      </tr>
      <tr>
      <td>
      <br/>
      <img src='cid:".$cid_logo."' height='173' width='581' alt='logo Telkomsigma' />
      <h2>Hi ".$name.",</h3>
      <br/>
      <h4>One more step to activate your account!</h4>
      <br>

      <p style='text-align:center;'>
      <a class='btn' style='background-color: #1da1db; border-radius: 3px;
      box-shadow: 3px 3px 10px 3px #B7B7B7;' href='".base_url()."index.php/login/verifyEmail/".md5($email)."'>Activate Account &raquo;</a>
      </p>
      <br/>
      <p style='text-align: left'>Trouble activating? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
      </td>
      </tr>

      </table>
      </div>

      </td>

      </tr>
      </table>
      <!-- /BODY -->

      <!-- FOOTER -->
      <table class='footer-wrap' bgcolor='#FFFFFF'>
      <tr>
      <td></td>
      <td class='container'>

      <!-- content -->
      <div class='content' style='margin-top: -15px'>
      <table>
      <tr>
      <br/>

      </br/>
      </tr>
      </table>
      </div>
      <!-- /content -->

      </td>
      <td></td>
      </tr>
      </table>

      </body>

      </html>");

      if($this->email->send()){
        return true;
      }else{
        return false;
      }

    }
    function sendDeactivateInfo($email,$name){
  $this->load->library('email');
  $config['protocol']='smtp';
  $config['smtp_host']='smtp.sigma.co.id';
  $config['smtp_user']=SMTP_AUTH_USR;
  $config['smtp_pass']=SMTP_AUTH_PWD;
  $config['smtp_port']='587';
  $config['smtp_timeout']='100';
  $config['charset']    = 'utf-8';
  $config['newline']    = "\r\n";
  $config['mailtype'] = 'html';
  $config['validation'] = TRUE;
  $this->email->initialize($config);
  $this->email->from('prouds.support@sigma.co.id', 'Project & Resources Development System');
  $this->email->to($email);
  $logo=base_url()."asset/image/logo_new_sigma1.png";
  $css=base_url()."asset/css/confirm.css";
  $this->email->attach($logo);
  $this->email->attach($css);
  $cid_logo = $this->email->attachment_cid($logo);
  $this->email->subject('Verification PROUDS Account');
  $this->email->message("<!DOCTYPE html>
  <html>
  <head>
  <meta name='viewport' content='width=device-width' />
  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
  <title>Account Activation</title>

  <style>
  /* -------------------------------------
  GLOBAL
  ------------------------------------- */
  * {
    margin:0;
    padding:0;
  }
  * { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; }

  img {
    max-width: 100%;
  }
  .collapse {
    margin:0;
    padding:0;
  }
  body {
    -webkit-font-smoothing:antialiased;
    -webkit-text-size-adjust:none;
    width: 100%!important;
    height: 100%;
  }


  /* -------------------------------------
  ELEMENTS
  ------------------------------------- */
  a { color: #2BA6CB;}

  .btn {
    text-decoration:none;
    color:#FFF;
    background-color: #1da1db;
    width:80%;
    padding:15px 10%;
    font-weight:bold;
    text-align:center;
    cursor:pointer;
    display:inline-block;
    border-radius: 5px;
    box-shadow: 3px 3px 3px 1px #EBEBEB;
  }

  p.callout {
    padding:15px;
    text-align:center;
    background-color:#ECF8FF;
    margin-bottom: 15px;
  }
  .callout a {
    font-weight:bold;
    color: #2BA6CB;
  }

  .column table { width:100%;}
  .column {
    width: 300px;
    float:left;
  }
  .column tr td { padding: 15px; }
  .column-wrap {
    padding:0!important;
    margin:0 auto;
    max-width:600px!important;
  }
  .columns .column {
    width: 280px;
    min-width: 279px;
    float:left;
  }
  table.columns, table.column, .columns .column tr, .columns .column td {
    padding:0;
    margin:0;
    border:0;
    border-collapse:collapse;
  }

  /* -------------------------------------
  HEADER
  ------------------------------------- */
  table.head-wrap { width: 100%;}

  .header.container table td.logo { padding: 15px; }
  .header.container table td.label { padding: 15px; padding-left:0px;}


  /* -------------------------------------
  BODY
  ------------------------------------- */
  table.body-wrap { width: 100%;}


  /* -------------------------------------
  FOOTER
  ------------------------------------- */
  table.footer-wrap { width: 100%;	clear:both!important;
  }
  .footer-wrap .container td.content  p { border-top: 1px solid rgb(215,215,215); padding-top:15px;}
  .footer-wrap .container td.content p {
    font-size:10px;
    font-weight: bold;

  }


  /* -------------------------------------
  TYPOGRAPHY
  ------------------------------------- */
  h1,h2,h3,h4,h5,h6 {
    font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; line-height: 1.1; margin-bottom:15px; color:#000;
  }
  h1 small, h2 small, h3 small, h4 small, h5 small, h6 small { font-size: 60%; color: #6f6f6f; line-height: 0; text-transform: none; }

  h1 { font-weight:200; font-size: 44px;}
  h2 { font-weight:200; font-size: 37px;}
  h3 { font-weight:500; font-size: 27px;}
  h4 { font-weight:500; font-size: 23px;}
  h5 { font-weight:900; font-size: 17px;}
  h6 { font-weight:900; font-size: 14px; text-transform: uppercase; color:#444;}

  .collapse { margin:0!important;}

  p, ul {
    margin-bottom: 10px;
    font-weight: normal;
    font-size:14px;
    line-height:1.6;
  }
  p.lead { font-size:17px; }
  p.last { margin-bottom:0px;}

  ul li {
    margin-left:5px;
    list-style-position: inside;
  }

  hr {
    border: 0;
    height: 0;
    border-top: 1px dotted rgba(0, 0, 0, 0.1);
    border-bottom: 1px dotted rgba(255, 255, 255, 0.3);
  }


  /* -------------------------------------
  Shopify
  ------------------------------------- */

  .products {
    width:100%;
    height:40px;padding
    margin:10px 0 10px 0;
  }
  .products img {
    float:left;
    height:40px;
    width:auto;
    margin-right:20px;
  }
  .products span {
    font-size:17px;
  }


  /* ---------------------------------------------------
  RESPONSIVENESS
  Nuke it from orbit. It's the only way to be sure.
  ------------------------------------------------------ */

  /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
  .container {
    display:block!important;
    max-width:600px!important;
    margin:0 auto!important; /* makes it centered */
    clear:both!important;
  }

  /* This should also be a block element, so that it will fill 100% of the .container */
  .content {
    padding: 15px 15px 0 15px;
    max-width:600px;
    margin:0 auto;
    display:block;
  }

  /* Let's make sure tables in the content area are 100% wide */
  .content table { width: 100%; }

  /* Be sure to place a .clear element after each set of columns, just to be safe */
  .clear { display: block; clear: both; }


  /* -------------------------------------------
  PHONE
  For clients that support media queries.
  Nothing fancy.
  -------------------------------------------- */
  @media only screen and (max-width: 600px) {

    a[class='btn'] { display:block!important; margin-bottom:10px!important; background-image:none!important; margin-right:0!important;}

    div[class='column'] { width: auto!important; float:none!important;}

    table.social div[class='column'] {
      width:auto!important;
    }

  }

  </style>
  </head>

  <body bgcolor='#FFFFFF'>\
  <table class='head-wrap' bgcolor='#FFFFFF'>
  <tr>
  <td></td>
  <td class='header container'>

  <div class='content'>
  <table bgcolor='#FFFFFF'>
  <tr>
  <td>

  </td>

  </tr>
  </table>
  </div>

  </td>
  <td></td>
  </tr>
  </table>
  <table class='body-wrap'>
  <tr>
  <td></td>
  <td class='container' bgcolor='#FFFFFF'>

  <div class='content'>
  <table>
  <tr>
  <td align='center'>
  </td>
  </tr>
  <tr>
  <td>
  <br/>
  <img src='cid:".$cid_logo."' height='173' width='581' alt='logo Telkomsigma' />
  <h2>Hi ".$name.",</h3>
  <br/>
  <h4>Your PROMS account has been deactivated.</h4>
  <br>
  <h4>Thank you.</h4>
  <br/>
  <p style='text-align: left'>Trouble activating? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
  </td>
  </tr>

  </table>
  </div>

  </td>

  </tr>
  </table>
  <!-- /BODY -->

  <!-- FOOTER -->
  <table class='footer-wrap' bgcolor='#FFFFFF'>
  <tr>
  <td></td>
  <td class='container'>

  <!-- content -->
  <div class='content' style='margin-top: -15px'>
  <table>
  <tr>
  <br/>

  </br/>
  </tr>
  </table>
  </div>
  <!-- /content -->

  </td>
  <td></td>
  </tr>
  </table>

  </body>

  </html>");

  if($this->email->send()){
    return true;
  }else{
    return false;
  }


}
    function sendActivateInfo($email,$name){
  $this->load->library('email');
  $config['protocol']='smtp';
  $config['smtp_host']='smtp.sigma.co.id';
  $config['smtp_user']=SMTP_AUTH_USR;
  $config['smtp_pass']=SMTP_AUTH_PWD;
  $config['smtp_port']='587';
  $config['smtp_timeout']='100';
  $config['charset']    = 'utf-8';
  $config['newline']    = "\r\n";
  $config['mailtype'] = 'html';
  $config['validation'] = TRUE;
  $this->email->initialize($config);
  $this->email->from('prouds.support@sigma.co.id', 'Project & Resources Development System');
  $this->email->to($email);
  $logo=base_url()."asset/image/logo_new_sigma1.png";
  $css=base_url()."asset/css/confirm.css";
  $this->email->attach($logo);
  $this->email->attach($css);
  $cid_logo = $this->email->attachment_cid($logo);
  $this->email->subject('Verification PROUDS Account');
  $this->email->message("<!DOCTYPE html>
  <html>
  <head>
  <meta name='viewport' content='width=device-width' />
  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
  <title>Account Activation</title>

  <style>
  /* -------------------------------------
  GLOBAL
  ------------------------------------- */
  * {
    margin:0;
    padding:0;
  }
  * { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; }

  img {
    max-width: 100%;
  }
  .collapse {
    margin:0;
    padding:0;
  }
  body {
    -webkit-font-smoothing:antialiased;
    -webkit-text-size-adjust:none;
    width: 100%!important;
    height: 100%;
  }


  /* -------------------------------------
  ELEMENTS
  ------------------------------------- */
  a { color: #2BA6CB;}

  .btn {
    text-decoration:none;
    color:#FFF;
    background-color: #1da1db;
    width:80%;
    padding:15px 10%;
    font-weight:bold;
    text-align:center;
    cursor:pointer;
    display:inline-block;
    border-radius: 5px;
    box-shadow: 3px 3px 3px 1px #EBEBEB;
  }

  p.callout {
    padding:15px;
    text-align:center;
    background-color:#ECF8FF;
    margin-bottom: 15px;
  }
  .callout a {
    font-weight:bold;
    color: #2BA6CB;
  }

  .column table { width:100%;}
  .column {
    width: 300px;
    float:left;
  }
  .column tr td { padding: 15px; }
  .column-wrap {
    padding:0!important;
    margin:0 auto;
    max-width:600px!important;
  }
  .columns .column {
    width: 280px;
    min-width: 279px;
    float:left;
  }
  table.columns, table.column, .columns .column tr, .columns .column td {
    padding:0;
    margin:0;
    border:0;
    border-collapse:collapse;
  }

  /* -------------------------------------
  HEADER
  ------------------------------------- */
  table.head-wrap { width: 100%;}

  .header.container table td.logo { padding: 15px; }
  .header.container table td.label { padding: 15px; padding-left:0px;}


  /* -------------------------------------
  BODY
  ------------------------------------- */
  table.body-wrap { width: 100%;}


  /* -------------------------------------
  FOOTER
  ------------------------------------- */
  table.footer-wrap { width: 100%;	clear:both!important;
  }
  .footer-wrap .container td.content  p { border-top: 1px solid rgb(215,215,215); padding-top:15px;}
  .footer-wrap .container td.content p {
    font-size:10px;
    font-weight: bold;

  }


  /* -------------------------------------
  TYPOGRAPHY
  ------------------------------------- */
  h1,h2,h3,h4,h5,h6 {
    font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; line-height: 1.1; margin-bottom:15px; color:#000;
  }
  h1 small, h2 small, h3 small, h4 small, h5 small, h6 small { font-size: 60%; color: #6f6f6f; line-height: 0; text-transform: none; }

  h1 { font-weight:200; font-size: 44px;}
  h2 { font-weight:200; font-size: 37px;}
  h3 { font-weight:500; font-size: 27px;}
  h4 { font-weight:500; font-size: 23px;}
  h5 { font-weight:900; font-size: 17px;}
  h6 { font-weight:900; font-size: 14px; text-transform: uppercase; color:#444;}

  .collapse { margin:0!important;}

  p, ul {
    margin-bottom: 10px;
    font-weight: normal;
    font-size:14px;
    line-height:1.6;
  }
  p.lead { font-size:17px; }
  p.last { margin-bottom:0px;}

  ul li {
    margin-left:5px;
    list-style-position: inside;
  }

  hr {
    border: 0;
    height: 0;
    border-top: 1px dotted rgba(0, 0, 0, 0.1);
    border-bottom: 1px dotted rgba(255, 255, 255, 0.3);
  }


  /* -------------------------------------
  Shopify
  ------------------------------------- */

  .products {
    width:100%;
    height:40px;padding
    margin:10px 0 10px 0;
  }
  .products img {
    float:left;
    height:40px;
    width:auto;
    margin-right:20px;
  }
  .products span {
    font-size:17px;
  }


  /* ---------------------------------------------------
  RESPONSIVENESS
  Nuke it from orbit. It's the only way to be sure.
  ------------------------------------------------------ */

  /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
  .container {
    display:block!important;
    max-width:600px!important;
    margin:0 auto!important; /* makes it centered */
    clear:both!important;
  }

  /* This should also be a block element, so that it will fill 100% of the .container */
  .content {
    padding: 15px 15px 0 15px;
    max-width:600px;
    margin:0 auto;
    display:block;
  }

  /* Let's make sure tables in the content area are 100% wide */
  .content table { width: 100%; }

  /* Be sure to place a .clear element after each set of columns, just to be safe */
  .clear { display: block; clear: both; }


  /* -------------------------------------------
  PHONE
  For clients that support media queries.
  Nothing fancy.
  -------------------------------------------- */
  @media only screen and (max-width: 600px) {

    a[class='btn'] { display:block!important; margin-bottom:10px!important; background-image:none!important; margin-right:0!important;}

    div[class='column'] { width: auto!important; float:none!important;}

    table.social div[class='column'] {
      width:auto!important;
    }

  }

  </style>
  </head>

  <body bgcolor='#FFFFFF'>\
  <table class='head-wrap' bgcolor='#FFFFFF'>
  <tr>
  <td></td>
  <td class='header container'>

  <div class='content'>
  <table bgcolor='#FFFFFF'>
  <tr>
  <td>

  </td>

  </tr>
  </table>
  </div>

  </td>
  <td></td>
  </tr>
  </table>
  <table class='body-wrap'>
  <tr>
  <td></td>
  <td class='container' bgcolor='#FFFFFF'>

  <div class='content'>
  <table>
  <tr>
  <td align='center'>
  </td>
  </tr>
  <tr>
  <td>
  <br/>
  <img src='cid:".$cid_logo."' height='173' width='581' alt='logo Telkomsigma' />
  <h2>Hi ".$name.",</h3>
  <br/>
  <h4>Your PROMS account has been activated.</h4>
  <br>
  <h4>Thank you.</h4>
  <br/>
  <p style='text-align: left'>Trouble activating? Contact us at <a href='mailto:prouds.support@sigma.co.id?Subject=Need%20help' target='_top'>prouds.support@sigma.co.id</a></p>
  </td>
  </tr>

  </table>
  </div>

  </td>

  </tr>
  </table>
  <!-- /BODY -->

  <!-- FOOTER -->
  <table class='footer-wrap' bgcolor='#FFFFFF'>
  <tr>
  <td></td>
  <td class='container'>

  <!-- content -->
  <div class='content' style='margin-top: -15px'>
  <table>
  <tr>
  <br/>

  </br/>
  </tr>
  </table>
  </div>
  <!-- /content -->

  </td>
  <td></td>
  </tr>
  </table>

  </body>

  </html>");

  if($this->email->send()){
    return true;
  }else{
    return false;
  }


}
function sendVerificationManual($email,$name,$namevendor){
  $this->load->library('email');
  $config['protocol']='smtp';
  $config['smtp_host']='smtp.sigma.co.id';
  $config['smtp_user']=SMTP_AUTH_USR;
  $config['smtp_pass']=SMTP_AUTH_PWD;
  $config['smtp_port']='587';
  $config['smtp_timeout']='100';
  $config['charset']    = 'utf-8';
  $config['newline']    = "\r\n";
  $config['mailtype'] = 'html';
  $config['validation'] = TRUE;
  $this->email->initialize($config);
  $this->email->from('prouds.support@sigma.co.id', 'Project & Resources Development System');
  $this->email->to($email);
  $logo=base_url()."asset/image/logo_new_sigma1.png";
  $css=base_url()."asset/css/confirm.css";
  $this->email->attach($logo);
  $this->email->attach($css);
  $cid_logo = $this->email->attachment_cid($logo);
  $this->email->subject('Verification PROUDS Account');
  $this->email->message("<!DOCTYPE html>
  <html>
  <head>
  <meta name='viewport' content='width=device-width' />
  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
  <title>Account Activation</title>

  <style>
  /* -------------------------------------
  GLOBAL
  ------------------------------------- */
  * {
    margin:0;
    padding:0;
  }
  * { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; }

  img {
    max-width: 100%;
  }
  .collapse {
    margin:0;
    padding:0;
  }
  body {
    -webkit-font-smoothing:antialiased;
    -webkit-text-size-adjust:none;
    width: 100%!important;
    height: 100%;
  }


  /* -------------------------------------
  ELEMENTS
  ------------------------------------- */
  a { color: #2BA6CB;}

  .btn {
    text-decoration:none;
    color:#FFF;
    background-color: #1da1db;
    width:80%;
    padding:15px 10%;
    font-weight:bold;
    text-align:center;
    cursor:pointer;
    display:inline-block;
    border-radius: 5px;
    box-shadow: 3px 3px 3px 1px #EBEBEB;
  }

  p.callout {
    padding:15px;
    text-align:center;
    background-color:#ECF8FF;
    margin-bottom: 15px;
  }
  .callout a {
    font-weight:bold;
    color: #2BA6CB;
  }

  .column table { width:100%;}
  .column {
    width: 300px;
    float:left;
  }
  .column tr td { padding: 15px; }
  .column-wrap {
    padding:0!important;
    margin:0 auto;
    max-width:600px!important;
  }
  .columns .column {
    width: 280px;
    min-width: 279px;
    float:left;
  }
  table.columns, table.column, .columns .column tr, .columns .column td {
    padding:0;
    margin:0;
    border:0;
    border-collapse:collapse;
  }

  /* -------------------------------------
  HEADER
  ------------------------------------- */
  table.head-wrap { width: 100%;}

  .header.container table td.logo { padding: 15px; }
  .header.container table td.label { padding: 15px; padding-left:0px;}


  /* -------------------------------------
  BODY
  ------------------------------------- */
  table.body-wrap { width: 100%;}


  /* -------------------------------------
  FOOTER
  ------------------------------------- */
  table.footer-wrap { width: 100%;	clear:both!important;
  }
  .footer-wrap .container td.content  p { border-top: 1px solid rgb(215,215,215); padding-top:15px;}
  .footer-wrap .container td.content p {
    font-size:10px;
    font-weight: bold;

  }


  /* -------------------------------------
  TYPOGRAPHY
  ------------------------------------- */
  h1,h2,h3,h4,h5,h6 {
    font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; line-height: 1.1; margin-bottom:15px; color:#000;
  }
  h1 small, h2 small, h3 small, h4 small, h5 small, h6 small { font-size: 60%; color: #6f6f6f; line-height: 0; text-transform: none; }

  h1 { font-weight:200; font-size: 44px;}
  h2 { font-weight:200; font-size: 37px;}
  h3 { font-weight:500; font-size: 27px;}
  h4 { font-weight:500; font-size: 23px;}
  h5 { font-weight:900; font-size: 17px;}
  h6 { font-weight:900; font-size: 14px; text-transform: uppercase; color:#444;}

  .collapse { margin:0!important;}

  p, ul {
    margin-bottom: 10px;
    font-weight: normal;
    font-size:14px;
    line-height:1.6;
  }
  p.lead { font-size:17px; }
  p.last { margin-bottom:0px;}

  ul li {
    margin-left:5px;
    list-style-position: inside;
  }

  hr {
    border: 0;
    height: 0;
    border-top: 1px dotted rgba(0, 0, 0, 0.1);
    border-bottom: 1px dotted rgba(255, 255, 255, 0.3);
  }


  /* -------------------------------------
  Shopify
  ------------------------------------- */

  .products {
    width:100%;
    height:40px;padding
    margin:10px 0 10px 0;
  }
  .products img {
    float:left;
    height:40px;
    width:auto;
    margin-right:20px;
  }
  .products span {
    font-size:17px;
  }


  /* ---------------------------------------------------
  RESPONSIVENESS
  Nuke it from orbit. It's the only way to be sure.
  ------------------------------------------------------ */

  /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
  .container {
    display:block!important;
    max-width:600px!important;
    margin:0 auto!important; /* makes it centered */
    clear:both!important;
  }

  /* This should also be a block element, so that it will fill 100% of the .container */
  .content {
    padding: 15px 15px 0 15px;
    max-width:600px;
    margin:0 auto;
    display:block;
  }

  /* Let's make sure tables in the content area are 100% wide */
  .content table { width: 100%; }

  /* Be sure to place a .clear element after each set of columns, just to be safe */
  .clear { display: block; clear: both; }


  /* -------------------------------------------
  PHONE
  For clients that support media queries.
  Nothing fancy.
  -------------------------------------------- */
  @media only screen and (max-width: 600px) {

    a[class='btn'] { display:block!important; margin-bottom:10px!important; background-image:none!important; margin-right:0!important;}

    div[class='column'] { width: auto!important; float:none!important;}

    table.social div[class='column'] {
      width:auto!important;
    }

  }

  </style>
  </head>

  <body bgcolor='#FFFFFF'>\
  <table class='head-wrap' bgcolor='#FFFFFF'>
  <tr>
  <td></td>
  <td class='header container'>

  <div class='content'>
  <table bgcolor='#FFFFFF'>
  <tr>
  <td>

  </td>

  </tr>
  </table>
  </div>

  </td>
  <td></td>
  </tr>
  </table>
  <table class='body-wrap'>
  <tr>
  <td></td>
  <td class='container' bgcolor='#FFFFFF'>

  <div class='content'>
  <table>
  <tr>
  <td align='center'>
  </td>
  </tr>
  <tr>
  <td>
  <br/>
  <img src='cid:".$cid_logo."' height='173' width='581' alt='logo Telkomsigma' />
  <h2>Hi ".$name.",</h3>
  <br/>
  <h4>User ".$namevendor." is already activated by PMO </h4>
  <br>

  <br/>
  <p style='text-align: left'>Trouble activating? Contact us at <a href='mailto:faishol.afandi@sigma.co.id?Subject=Need%20help' target='_top'>faishol.afandi@sigma.co.id</a></p>
  </td>
  </tr>

  </table>
  </div>

  </td>

  </tr>
  </table>
  <!-- /BODY -->

  <!-- FOOTER -->
  <table class='footer-wrap' bgcolor='#FFFFFF'>
  <tr>
  <td></td>
  <td class='container'>

  <!-- content -->
  <div class='content' style='margin-top: -15px'>
  <table>
  <tr>
  <br/>

  </br/>
  </tr>
  </table>
  </div>
  <!-- /content -->

  </td>
  <td></td>
  </tr>
  </table>

  </body>

  </html>");

  if($this->email->send()){
    return true;
  }else{
    return false;
  }

}
public function ExportDatatoExcelIn(){
	$query = $this->db->query("
	SELECT USER_ID, USER_NAME, EMAIL, LAST_LOGIN, USER_TYPE_ID,B.BU_NAME AS BU_NAME,C.PROF_NAME AS PROF_NAME,
    CASE A.IS_ACTIVE
    WHEN 0 THEN 'TIDAK AKTIF'
    WHEN 1 THEN 'AKTIF'
    END AS STATUS
FROM USERS A INNER JOIN P_BU B ON A.BU_ID=B.BU_ID
INNER JOIN PROFILE C ON A.PROF_ID=C.PROF_ID where USER_TYPE_ID='int'  ");

        if($query->num_rows() > 0){
            foreach($query->result() as $data){
                $hasil[] = $data;
            }
            return $hasil;
        }
	}
  public function ExportDatatoExcelExt(){
  	$query = $this->db->query("
  	SELECT USER_ID, USER_NAME, EMAIL, LAST_LOGIN, USER_TYPE_ID,B.BU_NAME AS BU_NAME,C.PROF_NAME AS PROF_NAME,
      CASE A.IS_ACTIVE
      WHEN 0 THEN 'TIDAK AKTIF'
      WHEN 1 THEN 'AKTIF'
      END AS STATUS
  FROM USERS A INNER JOIN P_BU B ON A.BU_ID=B.BU_ID
  INNER JOIN PROFILE C ON A.PROF_ID=C.PROF_ID where USER_TYPE_ID='ext'  ");

          if($query->num_rows() > 0){
              foreach($query->result() as $data){
                  $hasil[] = $data;
              }
              return $hasil;
          }
  	}

}
?>
