<?php

namespace Utils;

require_once dirname(__FILE__) . '/../DBHelper.php';

use \DBHelper;

class PortUtils {

    private static $commonPorts = array(20, 21, 22, 23, 25, 53, 80, 109, 110, 115, 119, 143, 156, 161, 443, 1080, 8080);

    public static function scanPorts($host, $ports) {
        $found = array();
        foreach ($ports as &$port) {
            if ($pf = @fsockopen($host, $port, $err, $err_string, 1)) {
                fwrite($pf, "C01 CAPABILITY\r\n");
                $response = fgets($pf);
                if (!$response)
                    $response = "";
                $service = DBhelper::getService($port);
                $found[] = array('port' => $port, 'status' => 'open', 'response' => $response, 'service' => $service);
                fclose($pf);
            }/* else {
              $found[] = array('port' => $port, 'status' => 'closed');
              } */
        }
        return $found;
    }

    public static function scanPortsWithRange($host, $portRanges) {
        $range = array();
        $found = array();
        foreach ($portRanges as &$portRange) {
            if (count($portRange) > 2 || $portRange[0] > $portRange[1])
                throw new Exception("You supplied invalid port range!");
            for ($i = $portRange[0]; $i <= $portRange[1]; $i++) {
                if (!in_array($i, $range)) {
                    $range[] = $i;
                }
            }
        }
        foreach ($range as &$port) {
            if ($pf = @fsockopen($host, $port, $err, $err_string, 1)) {
                fwrite($pf, "C01 CAPABILITY\r\n");
                $response = fgets($pf);
                if (!$response)
                    $response = "";
                $service = DBhelper::getService($port);
                $found[] = array('port' => $port, 'status' => 'open', 'response' => $response, 'service' => $service);
                fclose($pf);
            }/* else {
              $found[] = array('port' => $port, 'status' => 'closed');
              } */
        }
        return $found;
    }

    public static function scanCommonPorts($host) {
        return self::scanPorts($host, self::$commonPorts);
    }

}
