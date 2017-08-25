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
                "ACCESS_ID"=>1,
                "ACCESS_NAME"=>"Update Personal Timesheet",
                "TYPE"=>"BUSINESS"
            ],
            [
                "ACCESS_ID"=>2,
                "ACCESS_NAME"=>"Access Business Unit Overview",
                "TYPE"=>"BUSINESS",
            ],
            [
                "ACCESS_ID"=>3,
                "ACCESS_NAME"=>"Create Project",
                "TYPE"=>"BUSINESS",
            ],
            [
                "ACCESS_ID"=>4,
                "ACCESS_NAME"=>"Access All Project In Business Unit",
                "TYPE"=>"BUSINESS",
            ],
            [
                "ACCESS_ID"=>5,
                "ACCESS_NAME"=>"Approve Timesheet(Non-project)",
                "TYPE"=>"BUSINESS",
            ],
            [
                "ACCESS_ID"=>6,
                "ACCESS_NAME"=>"See Report Overview",
                "TYPE"=>"BUSINESS",
            ],
            [
                "ACCESS_ID"=>7,
                "ACCESS_NAME"=>"See Resource Report",
                "TYPE"=>"BUSINESS"
            ],
            [
                "ACCESS_ID"=>8,
                "ACCESS_NAME"=>"Download Report",
                "TYPE"=>"BUSINESS"
            ],
            [
                "ACCESS_ID"=>9,
                "ACCESS_NAME"=>"Approve/Deny Rebaseline",
                "TYPE"=>"BUSINESS"
            ]
        ];
        $dataurl= [
            [
                "ACCESS_ID" => 1,
                "ACCESS_URL"=>"timesheet/addtimesheet"
            ],
            [
                "ACCESS_ID" =>1 ,
                "ACCESS_URL"=>"timesheet/view"
            ],
            [
                "ACCESS_ID"=>2,
                "ACCESS_URL"=>"home/budetail"
            ],
            [
                "ACCESS_ID"=>3,
                "ACCESS_URL"=>"project/addproject_view"
            ],
            [
                "ACCESS_ID"=>3,
                "ACCESS_URL"=>"project/addproject_acion"
            ],
            [
                "ACCESS_ID"=>4,
                "ACCESS_URL"=>'accept'
            ],
            [
                "ACCESS_ID"=>5,
                "ACCESS_URL"=>"timesheet/confirmationtimesheet"
            ],
            [
                "ACCESS_ID"=>7,
                "ACCESS_URL"=>"report/r_people"
            ],
            [
                "ACCESS_ID"=>9,
                "ACCESS_URL"=>"project/accept_rebaseline"
            ],
            [
                "ACCESS_ID"=>9,
                "ACCESS_URL"=>"project/deny_rebaseline"
            ]
        ];/*
        foreach($dataurl as $d){

            $this->db->insert('ACCESS_URL',$d);
        }*/

    }
}