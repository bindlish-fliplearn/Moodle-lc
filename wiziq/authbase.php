<?php
// This file is part of Wiziq - http://www.wiziq.com/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Internal library of functions for module wiziq
 *
 * All the wiziq specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_wiziq
 * @copyright  www.wiziq.com 
 * @author     dinkar@wiziq.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

set_time_limit(0);

/**
 * This class is used for data integrity and the authenticity of a message.
 * 
 * @package    mod_wiziq
 * @copyright www.wiziq.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wiziq_authbase {

    /**
     * This function defines the secret access key and access key.
     *
     * @param string $wiziq_secretacesskey
     * @param string $wiziq_access_key
     */
    public function __construct($wiziq_secretacesskey, $wiziq_access_key) {
        $this->wiziq_secretacesskey=$wiziq_secretacesskey;
        $this->wiziq_access_key=$wiziq_access_key;
    }
    /**
     * This function generates the timestamp.
     *
     * @return integer time.
     */
    public function wiziq_generatetimestamp() {
        return time();
    }
    /**
     * This function generates the signature in order to send a http request.
     * 
     * @param string $methodname name of the method.
     * @param string $requestparameters parameters that are required for http request.
     * 
     * @return string
     */
    public function wiziq_generatesignature($methodname, &$requestparameters) {
        $signaturebase="";
        $wiziq_secretacesskey = urlencode($this->wiziq_secretacesskey);
        $requestparameters["access_key"] = $this->wiziq_access_key;
        $requestparameters["timestamp"] =$this->wiziq_generatetimestamp();
        $requestparameters["method"] = $methodname;
        foreach ($requestparameters as $key => $value) {
            $signaturebaselenght = strlen($signaturebase);
            if ($signaturebaselenght > 0) {
                $signaturebase.="&";
            }
            $signaturebase.="$key=$value";
        }
        return base64_encode($this->wiziq_hmacsha1($wiziq_secretacesskey, $signaturebase));
    }
    /**
     * This function generates the hash based message authentication code(hmac)
     * using cryptographic hash function sha1. 
     * 
     * @param string $key cryptographic key.
     * @param string $data the data which will be appended.
     * 
     * @return string $hmac
     */
    public function wiziq_hmacsha1($key, $data) {
        $blocksize=64;
        $hashfunc='sha1';
        $keylenght = strlen($key);
        if ($keylenght > $blocksize) {
            $key=pack('H*', $hashfunc($key));
        }
        $key=str_pad($key, $blocksize, chr(0x00));
        $ipad=str_repeat(chr(0x36), $blocksize);
        $opad=str_repeat(chr(0x5c), $blocksize);
        $hmac = pack(
            'H*', $hashfunc(
                ($key^$opad).pack(
                    'H*', $hashfunc(
                        ($key^$ipad).$data
                    )
                )
            )
        );
        return $hmac;
    }
}//end class wiziq_authbase

/**
 * This class defines a http request function.
 * 
 * @package    mod_wiziq
 * @copyright www.wiziq.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wiziq_httprequest {
    /**
     * This function generates a http request using curl.
     * 
     * @param string $url this is wiziq url to which request is to be send.
     * @param array $data which contains the request paramters.
     * @param string $optional_headers
     * 
     * @return string $response.
     */
    public function wiziq_do_post_request($url, $data, $optional_headers = null) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url );
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1 );
            $response = curl_exec($ch);
            return $response;
            // close cURL resource, and free up system resources
            curl_close($ch);
        } catch (Exception $e) {
            $errorexecption = $e->getMessage();
            $errormsg = get_string('errorinservice', 'wiziq'). " " . $errorexecption;
            print_error($errormsg);
        }
    }
}//end class wiziq_httprequest
