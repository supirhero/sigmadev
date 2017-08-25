<?php
Class M_mis extends CI_Model
{
    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('M_report');
    }

    public function getcustomerMIS(){
      $ch=curl_init();
      $request='http://10.210.20.2/api/index.php/mis/customer';
      curl_setopt($ch, CURLOPT_HTTPGET, 1);
  		curl_setopt($ch, CURLOPT_URL, $request);
  		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  		$html = curl_exec($ch);
  		$curl_errno = curl_errno($ch);
  		$curl_error = curl_error($ch);
  		curl_close($ch);
      return $html;
      }

      public function getpartnerMIS(){
        $ch=curl_init();
        $request='http://10.210.20.2/api/index.php/mis/vendor';
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
    		curl_setopt($ch, CURLOPT_URL, $request);
    		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		$html = curl_exec($ch);
    		$curl_errno = curl_errno($ch);
    		$curl_error = curl_error($ch);
    		curl_close($ch);
        return $html;
      }
}
