<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mdlTesting');
    }
    public function index(){
        print_r($this->mdlTesting->tes());
        //echo $_SERVER['REQUEST_METHOD'];
    }
}