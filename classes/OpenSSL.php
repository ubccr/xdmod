<?php

use CCR\DB;

class OpenSSL {

   public static function publicKeyDecrypt($data, $publicKey){

      // The raw PHP decryption functions appear to work
      // on 128 Byte chunks. So this decrypts long text
      // encrypted with ssl_encrypt().

      $maxlength = 128;
      $output = '';

      while($data){

         $input = substr($data, 0, $maxlength);
         $data = substr($data, $maxlength);

         $ok = openssl_public_decrypt($input, $out, $publicKey);

         $output .= $out;

      }//while

      return $output;

   }//publicKeyDecrypt

   // ----------------------------------------------------------------
               
   public static function publicKeyEncrypt($sensitiveData, $publicKey){
      
      // Assumes 1024-bit key and encrypts in chunks.

      $maxlength = 117;
      $output = '';
      
      while($sensitiveData) {
      
         $input = substr($sensitiveData, 0, $maxlength);
         $sensitiveData = substr($sensitiveData, $maxlength);

         $ok = openssl_public_encrypt($input, $encrypted, $publicKey);

         $output .= $encrypted;
         
      }//while

      return $output;
   
   }//publicKeyEncrypt

   // ----------------------------------------------------------------
      
   public static function getIdentifierFromAPIKey($api_key) {
   
      $pdo = DB::factory('database');
      
      $response = $pdo->query("SELECT identifier FROM moddb.APIKeys WHERE api_key=:api_key", array('api_key' => $api_key));
      
      if (count($response) == 0) {
         return NULL;
      }
      
      return $response[0]['identifier'];
   
   }//getIdentifierFromAPIKey
   
   // ----------------------------------------------------------------
   
   public static function getPublicKeyFromAPIKey($api_key) {
   
      $pdo = DB::factory('database');
      
      $response = $pdo->query("SELECT public_key FROM moddb.APIKeys WHERE api_key=:api_key", array('api_key' => $api_key));
      
      if (count($response) == 0) {
         throw new Exception('The API key specified is invalid', EXCEPTION_PKI);
      }
      
      $publicKey = openssl_pkey_get_public($response[0]['public_key']);
      
      return $publicKey;
   
   }//getPublicKeyFromAPIKey
   

}//class OpenSSL

?>
