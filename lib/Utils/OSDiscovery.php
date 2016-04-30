<?php

namespace Utils;

require_once dirname(__FILE__) . '/../DBHelper.php';

use \DBHelper;

class OSDiscovery {

    public static function getServer($host) {
        $results = array();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $host);
        curl_setopt($curl, CURLOPT_HEADER, true);
        $response = curl_exec($curl);
        $matches = array();
        $pattern = '/^.*\bServer\b.*$/m';
        preg_match($pattern, $response, $matches);

        foreach ($matches as &$header) {
            $header_split = explode(":", $header);
            $results[] = trim($header_split[1]);
        }
        
        if(count($results) == 0){
            return "Unknown";
        }
       
        return $results[0];
    }

    public static function getLocation($host) {
        $location = DBhelper::getLocation($host);
        return $location;
    }

}
