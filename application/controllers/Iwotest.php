<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
class Iwotest extends CI_Controller {

    public $datajson = array();

    public function __construct()
    {
        parent::__construct();


    }

    public function getIwo(){
        $offset = $this->uri->segment(3);
        if($offset == 0 || $offset == "" || $offset == null){
            $offset = 0;
        }

         //get iwo
        @$json = file_get_contents('http://180.250.18.227/api/index.php/mis/iwo/');
        $IWO = array();
        $IWO = json_decode($json, true);

        $IWO_VIEW['iwo'] = [];
        for($i = $offset;$i < $offset+49 ; $i++){
            array_push($IWO_VIEW['iwo'],$IWO[$i]);
        }

        echo json_encode($IWO_VIEW);

    }

}