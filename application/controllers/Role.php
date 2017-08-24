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
                "ACCESS_NAME"=>"Update Personal Timesheet",
                "TYPE"=>"BUSINESS"
            ],
            [
                "ACCESS_NAME"=>"Access Business Unit Overview",
                "TYPE"=>"BUSINESS",
                "URL"=>"home/budetail"
            ],
            [
                "ACCESS_NAME"=>"Create Project",
                "TYPE"=>"BUSINESS",
                "URL"=>"project/addproject_acion"
            ],
            [
                "ACCESS_NAME"=>"Access All Project",
                "TYPE"=>"BUSINESS",
                "URL"=>"true"
            ],
            [
                "ACCESS_NAME"=>"Approve Timesheet(Non-project)",
                "TYPE"=>"BUSINESS",
                "URL"=>"true"
            ],
            [
                "ACCESS_NAME"=>"See Report Overview",
                "TYPE"=>"BUSINESS",
                "URL"=>""
            ],
            [
                "ACCESS_NAME"=>"See Resource Report",
                "TYPE"=>"BUSINESS"
            ],
            [
                "ACCESS_NAME"=>"Download Report",
                "TYPE"=>"BUSINESS"
            ],
            [
                "ACCESS_NAME"=>"Approve/Deny Rebaseline",
                "TYPE"=>"BUSINESS"
            ],
            [
                "ACCESS_NAME"=>"See Report Overview",
                "TYPE"=>"BUSINESS"
            ]
        ];
    }
}