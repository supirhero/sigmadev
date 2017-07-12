<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include_once APPPATH."../vendor/autoload.php";
use \Firebase\JWT\JWT;

class Token{
    function __construct()
    {
    }

    //decode token
    public function decodetoken($token){
        try{
            return JWT::decode($token,"'u&{<wbUaJ58dcx",array('HS256'));
        }
        catch (Exception $ex){
            $returndata['login_error'] = $ex->getMessage();
            return json_encode($returndata);
        }
    }

    //check token
    public function checktoken($token){
        try{
            JWT::decode($token,"'u&{<wbUaJ58dcx",array('HS256'));
            echo true;
        }
        catch (Exception $ex){
            $returndata['error'] = $ex->getMessage();
            print_r(json_encode($returndata));
        }
    }

    //create token
    public function createtoken($datauser){
        $tokenId    = base64_encode(mcrypt_create_iv(32));
        $issuedAt   = time();
        $notBefore  = $issuedAt + 10;  //Adding 10 seconds
        $expire     = $notBefore + 7200; // Adding 2 hours
        $serverName = 'http://45.77.45.126/dev'; /// set your domain name


        /*
         * Create the token as an array
         */
        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,       // Issuer
            'nbf'  => $notBefore,        // Not before
            'exp'  => $expire,           // Expire
            'data' => $datauser
        ];
        $secretKey ="'u&{<wbUaJ58dcx";
        /// Here we will transform this array into JWT:
        $jwt = JWT::encode(
            $data, //Data to be encoded in the JWT
            $secretKey, // The signing key
            'HS256'
        );
        return $jwt;
    }
}

?>