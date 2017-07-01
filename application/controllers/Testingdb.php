<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Testingdb extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mdlTesting');
    }

    public function index(){
        print_r($this->mdlTesting->tes());
    }
}

