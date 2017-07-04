<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model('M_login');
        $this->load->model('M_user');
        //$this->load->library("security");
    }

    function index($error=null)
    {
        $error=$this->uri->segment(3,0);
        if(isset($error)||$error!=""){
            $data['error']=$error;
        }
        $this->load->helper(array('form'));
        if($this->session->userdata('logged_in'))
        {
            $this->welcome();
        }else{
            $data['title']= 'error';
            $data['message']='username atau password tidak cocok';
            print_r(json_encode($data));
            //$this->load->view('footer_view',$data);
        }

    }

    //if login success go to home
    public function welcome()
    {
        $data['title']= 'Welcome';
        //$this->load->view('header_view',$data);
        //$data['user_id']=($this->M_login->tampil());
        //$this->load->view('v_home.php', $data);
        //$this->load->view('footer_view',$data);
        $id=$this->session->userdata('USER_ID');
        $this->M_user->lastLogin($id);

        //print_r($this->session);
        //go to home route
        redirect('/Home');
    }

    /*LOGIN ACTIVITY*/
    //for login activity
    function login()
    {
        if($_POST['user_id'] != "" && $_POST['password'] != "" && $_POST['fpid'] != ""){

            $user_id = $this->input->post('user_id');
            $password = $this->input->post('password');

            //$sso variable for authetication login value
            $sso =$this->sso($user_id,$password);
            if($sso['STATUS']=='1'){
                if(isset($sso['EMP_ID'])){
                    $result=$this->M_login->loginsso($sso['EMP_ID']);}
                else {
                    $result=$this->M_login->loginsso($sso['NIK']);
                }
                redirect('/login/welcome');
            }else {
                $password = md5($password);
                $cek=$this->M_login->validateLogin($user_id,$password);
                if($cek=='0'){
                    $result=$this->M_login->login($user_id,$password);
                    if($result)

                        redirect('/login/welcome');
                }
            }
            //print_r($result);
            redirect('/login/index/'.$cek);
        }
        else{
            $data['title']= 'error';
            $data['message']="Input User dan password tidak bolek kosong";
            print_r(json_encode($data));
        }

    }
    //authetivication login
    function sso($email,$password)
    {

        //----function 1-----///
        //  $url = 'sso.telkomsigma.co.id/index.php/login/logindo';
        //  $url = "http://180.250.18.227/apiv2/auth/byemail/".$email.'/'.base64_encode($password);
        /*$fields = array('mail_user'=>$email, 'mail_pass'=>$password);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, true);  // Tell cURL you want to post something
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields); // Define what you want to post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the output in string format
        $output = curl_exec ($ch); // Execute

        curl_close ($ch); // Close cURL handle*/
        //----function 1-----///


        //----function 2-----///
        /*  $postdata = http_build_query(
        array(
        'mail_user' => $email,
        'mail_pass' => $password
        )
      );

      $opts = array('http' =>
      array(
      'method'  => 'POST',
      'header'  => 'Content-type: application/x-www-form-urlencoded',
      'content' => $postdata
      )
    );

    $context  = stream_context_create($opts);

    $output = file_get_contents('http://sso.telkomsigma.co.id/index.php/login/logindo', false, $context);*/
        //----function 2-----///

        //----function 3-----///
        $url = 'sso.telkomsigma.co.id/index.php/login/logindo';
        //$url = "http://10.210.20.2/apiv2/auth/byemail/".$email.'/'.base64_encode($password);
        $fields = array('mail_user'=>$email, 'mail_pass'=>$password);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, true);  // Tell cURL you want to post something
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields); // Define what you want to post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the output in string format
        $output = curl_exec ($ch); // Execute

        curl_close ($ch); // Close cURL handle*/
        //----function 3-----///





        $id =$this->db->query("select  NVL(max(ID)+1, 1) as NEW_ID from LOGSSO")->row()->NEW_ID;
        $data = array(
            'ID' => $id ,
            'EMAIL' => $email,
            'JSON'=>$output,
            'TGL'=>date('Y-m-d H:i:s')
        );
        $this->db->insert('LOGSSO', $data);
        $hasil =(array)(json_decode($output));
        return $hasil;
    }

    //for registrating new user/vendor
    public function doRegistration()
    {

        $regType=$this->input->post('Submit');


        switch($regType){
            //check if vendor exist
            case 'registVendor':
                if($this->M_login->validateUser('V_USER_ID')==TRUE){
                    $errorMsg='err1';
                    $data['title']= 'error';
                    $data['message']=$errorMsg;
                    print_r(json_encode($data));
                }elseif($this->M_login->validateUser('V_EMAIL')==TRUE){
                    $errorMsg='err2';
                    $data['title']= 'error';
                    $data['message']=$errorMsg;
                    print_r(json_encode($data));
                }elseif($this->M_login->validateUser('V_EMAIL_SUP')==FALSE){
                    $errorMsg='err4';
                    $data['title']= 'error';
                    $data['message']=$errorMsg;
                    print_r(json_encode($data));
                }
                else{
                    $this->M_login->addUserVendor();

                    $this->M_login->recordVerificationV();
                    $this->sendVerificationV($this->input->post('V_EMAIL_SUP'));
                    $data['title']= 'success';
                    $data['message']='Berhasil tambah vendor';
                    print_r(json_encode($data));
                }

                break;
            //check if user exist
            case 'registSigma':
                if($this->M_login->validateUser('USER_ID')==TRUE){
                    $errorMsg='err1';
                    $data['title']= 'error';
                    $data['message']=$errorMsg;
                    print_r(json_encode($data));
                }elseif($this->M_login->validateUser('EMAIL')==TRUE){
                    $errorMsg='err2';
                    $data['title']= 'error';
                    $data['message']=$errorMsg;
                    print_r(json_encode($data));
                }else{
                    $this->M_login->add_user();

                    $this->M_login->recordVerification();
                    $this->sendVerification($this->input->post('EMAIL'));
                    $data['title']= 'success';
                    $data['message']='Berhasil tambah user';
                    print_r(json_encode($data));
                }
                break;

        }

    }

    function logout()
    {
        $newdata = array(
            'USER_ID'   =>'',
            'USER_NAME'  =>'',
            'EMAIL'     => '',
            'PASSWORD' => '',
            'logged_in' => FALSE,
        );
        $this->session->unset_userdata($newdata);
        $this->session->sess_destroy();
        $data['title']= 'success';
        $data['message']='Berhasil Logout';
        print_r(json_encode($data));
    }

    //delete user
    function hapus($user_name){
        $where = array ('USER_NAME'=>$user_name);
        $this->M_data->hapus_data($where,'users');
        redirect('home');
    }


    function afterForget(){
        $data['data']=1;
        $data['text']="Password baru sudah terkirim, Silahkan cek email anda";
        $this->load->view('v_forget',$data);
    }

    function verifyEmail(){
        $email=$this->uri->segment(3,0);
        switch($this->M_login->activateRegister($email)){
            case '1':
                $data['data']=3;
                $data['text']="Account Anda berhasil terverifikasi. Anda akan otomatis pindah ke halaman login beberapa saat lagi";
                break;
            case '0':
                $data['data']=2;
                $data['text']="Account anda sudah terverifikasi sebelumnya, Silahka klik tombol untuk pindah ke halaman login";
                break;
        }
        print_r(json_encode($data));
        //$this->load->view('v_email_confirm',$data);
    }

    function sendVerification($email){
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
        //$this->email->to($email);
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
  <img src='cid:".$cid_logo."' alt='logo Telkomsigma' />
  <h2>Hi ".$this->input->post('USER_NAME').",</h3>
  <br/>
  <h4>One more step to activate your account!</h4>
  <br>

  <p style='text-align:center;'>
  <a class='btn' style='background-color: #1da1db; border-radius: 3px;
  box-shadow: 3px 3px 10px 3px #B7B7B7;' href='".base_url()."index.php/login/verifyEmail/".md5($this->input->post('EMAIL'))."'>Activate Account &raquo;</a>
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
            echo "sent ".$this->email->print_debugger();
        }

    }
    function sendVerificationV($email){
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
        //$this->email->to($email);
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
  <img src='cid:".$cid_logo."' alt='logo Telkomsigma' />
  <h2>Hi,</h3>
  <br/>
  <h4> User ".$this->input->post('V_USER_NAME')." need your approval to activate, please click link below for activation</h4>
  <br>

  <p style='text-align:center;'>
  <a class='btn' style='background-color: #1da1db; border-radius: 3px;
  box-shadow: 3px 3px 10px 3px #B7B7B7;' href='".base_url()."index.php/login/verifyEmail/".md5($this->input->post('V_EMAIL'))."'>Activate Account &raquo;</a>
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
            echo "sent ".$this->email->print_debugger();
        }

    }

    //forget password action
    function doforget(){
        {
            //if npass or cpass
            if($this->input->post('NPASS') == "" || $this->input->post('CPASS')==""){
                $data['title'] = 'error';
                $data['message'] = 'NPASS dan CPASS tidak boleh kosong';
                print_r(json_encode($data));
            }
            else{
                $npass=$this->input->post('NPASS');
                $cpass=$this->input->post('CPASS');
                $email=$this->input->post('EMAIL');
                if($npass==$cpass){
                    $this->M_login->updatePassword($email,md5($npass));
                    $data['title']= 'success';
                    $data['message'] = 'Berhasil update password';
                    print_r(json_encode($data));
                    //redirect('/login/submitPassword');
                }else{
                    $data['title']= 'error';
                    $data['message'] = 'gagal reset password';
                    print_r(json_encode($data));
                }
                // $this->M_login->recordGetPassword();
                // $this->sendResetPassword($this->input->post('EMAIL'));
                //redirect('/login/afterForget');
            }

        }
    }


    function verifyForgetPassword(){
        $email=$this->uri->segment(3,0);
        switch($this->M_login->activateGetPassword($email)){
            case '1':
                $data['data']=3;
                $data['text']="Account Anda berhasil terverifikasi. Anda akan otomatis pindah ke halaman login beberapa saat lagi";
                break;
            case '0':
                $data['data']=2;
                $data['text']="Account anda sudah terverifikasi sebelumnya, Silahka klik tombol untuk pindah ke halaman login";
                break;
        }
        $this->load->view('v_email_confirm',$data);
    }
    function sendResetPassword(){

        $this->M_login->recordGetPassword();
        $this->load->library('email');
        $email=$this->input->post('EMAIL');
        $name=$this->M_login->getName($email);
        $user_id=$this->M_login->getID($email);
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
        //$this->email->to($email);
        $logo=base_url()."asset/image/logo_new_sigma1.png";
        $css=base_url()."asset/css/confirm.css";
        $this->email->attach($logo);
        $this->email->attach($css);
        $cid_logo = $this->email->attachment_cid($logo);
        $this->email->subject('Change Password PROUDS Account');
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
  <h2>Hi, ".$name."</h3>
  <br/>

  <br>
  <p style='text-align:center;'>
  <a class='btn' style='background-color: #1da1db; border-radius: 3px;
  box-shadow: 3px 3px 10px 3px #B7B7B7;' href='".base_url()."index.php/login/afterAfterForget/".md5($this->input->post('EMAIL'))."'> Click link below to change your password &raquo;</a>

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
            echo "<script>alert('Sent')</script> ".$this->email->print_debugger();
            redirect("/user");
        }

    }


    //verification forget password . it triggered from email
    function afterAfterForget(){
        $email=$this->uri->segment(3, null);
        $data['email']=$this->M_login->activateGetPassword($email);

        print_r($data);
        $this->load->view("v_forget_password2.php",$data);
        //$this->load->model('M_login');






        /*
        else
        {
        echo "<script>alert('Gagal Register!')</script>";
        $this->register();
      }

      } */

    }
}




?>
