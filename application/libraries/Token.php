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

    public function refreshtoken_decode($oldtoken){
        $konfirmasi = $this->decodetoken($oldtoken);

        //if token is valid
        if(is_object($konfirmasi)){
            $returndata['refresh_error'] = 'Token Already Valid';
             return json_encode($returndata);
        }
        else{
            $konfirmasi = json_decode($konfirmasi);
            //if token is expired
            if($konfirmasi->login_error == 'Expired token'){
                $tks = explode('.', $oldtoken);
                list($headb64, $bodyb64, $cryptob64) = $tks;
                $arrayreturn = json_decode(base64_decode($bodyb64));
                $userdata = json_decode(json_encode($arrayreturn->data),true);

                $new_token['token'] = $this->createtoken($userdata);
                return json_encode($new_token);
            }
            else{
                $returndata['refresh_error'] = 'Not a valid Token';
                return json_encode($returndata);
            }
        }


    }
}

?>