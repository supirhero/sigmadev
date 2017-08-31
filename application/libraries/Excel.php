<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH."../vendor/autoload.php";

class Excel extends PHPExcel {
    public function __construct() {
        parent::__construct();
    }
}

