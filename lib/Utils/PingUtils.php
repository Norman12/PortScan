<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Utils;

/**
 * Description of PingUtils2
 *
 * @author Marcin
 */
class PingUtils {

    public static function ping($host, $count) {
        $result = array();
        $data = null;

        exec("ping -c $count $host", $data);
        $size = count($data);
        if ($size == 0) {
            return array("status" => "failure", "reason" => "$host is down");
        }
        $result["title"] = $data[0];

        for ($i = 1; $i <= $size - 4; $i++) {
            $line = $data[$i];
            if ($line != "") {
                $result["results"][] = $line;
            }
        }

        $result["statistics"][0] = $data[$size - 2];
        $result["statistics"][1] = $data[$size - 1];

        return $result;
    }

    public static function checkResponseTime($host) {
        $starttime = microtime(true);
        // supress error messages with @
        $file = @fsockopen($host, 80, $errno, $errstr, 10);
        $stoptime = microtime(true);
        $status = 0;

        if (!$file) {
            $status = -1;  // Site is down
        } else {
            fclose($file);
            $status = ($stoptime - $starttime) * 1000;
            $status = floor($status);
        }
        return $status;
    }

}
