<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Role extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('M_role');
    }

    function insert(){
        $data = [
            [
                
            ]
        ];
    }
}