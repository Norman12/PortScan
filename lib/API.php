<?php

/**
 * Description of API
 *
 * @author Marcin
 */
require_once 'Utils/Whois.php';
require_once 'Utils/PortUtils.php';
require_once 'Utils/PingUtils.php';
require_once 'Utils/OSDiscovery.php';

use \Exception;
use Utils\Whois;
use Utils\PortUtils;
use Utils\PingUtils;
use Utils\OSDiscovery;

class API {

    private static $special_chars = array("?", "!", "_");

    public static function getDNSRecords($host) {
        return dns_get_record($host, DNS_ALL);
    }

    public static function getWhoisLookup($host) {
        /* return WhoisUtils::LookupDomain($host); */
        //$arr = explode(".", $host);
        //$cnt = count($arr)-1;
        //$whois = new Whois($arr[$cnt-1].".".$arr[$cnt]);
        $whois = new Whois($host);
        $whois_answer = $whois->info();
        return $whois_answer;
    }

    public static function scanPorts($host, $queryString) {
        $tokenArray = explode(",", $queryString);
        $ports = array();
        $ranges = array();
        $portNum = 0;
        //Classify tokens and populate arrays
        foreach ($tokenArray as &$token) {
            $token = trim($token);
            if (strpos($token, "[") !== false && strpos($token, "]") !== false) {
                if (strpos($token, ";") == false || strpos($token, ",") !== false) {
                    throw new Exception("Wrong query string!");
                }
                $ranges[] = explode(";", str_replace(" ", "", (substr($token, 1, -1))));
            } else {
                $ports[] = $token;
            }
        }
        
        $portNum += count($ports);
        
        //Check and remove repeating ports
        foreach ($ranges as &$range) {
            $portNum += $range[1]-$range[0];
            foreach ($ports as $key => &$port) {
                if ($port >= $range[0] && $port <= $range[1]) {
                    unset($ports[$key]);
                }
            }
        }
        
        if($portNum >= 40){
            throw new Exception("Port number cannot exceed 40.");
        }

        $scannedArrays = PortUtils::scanPortsWithRange($host, $ranges);
        $scannedPorts = PortUtils::scanPorts($host, $ports);

        //Comparizon function
        function cmp($a, $b) {
            if ($a["port"] == $b["port"]) {
                return 0;
            }
            return ($a["port"] < $b["port"]) ? -1 : 1;
        }

        //Merge and sort arrays
        $result = array_merge($scannedPorts, $scannedArrays);
        usort($result, "cmp");

        return $result;
    }

    public static function scanCommonPorts($host) {
        return PortUtils::scanCommonPorts($host);
    }

    public static function getInfo($host) {
        $document = array();
        $document["server"] = OSDiscovery::getServer($host);
        $document["response_time"] = PingUtils::checkResponseTime($host) . "ms";
        if (!self::validateIP($host)) {
            $document["location"] = OSDiscovery::getLocation(gethostbynamel($host)[0]);
        } else {
            $document["location"] = OSDiscovery::getLocation($host);
        }
        return $document;
    }

    public static function getPing($host, $count) {
        return PingUtils::ping($host, $count);
    }

    public static function validateHost($host) {

        $hostr = str_replace(self::$special_chars, "", $host);

        if (self::validateDomain($hostr) || self::validateIP($hostr)) {
            return $hostr;
        } else {
            throw new Exception("please provide either valid domain name or IP address.");
        }
    }

    public static function validateIP($ip) {

        $ipr = str_replace(self::$special_chars, "", $ip);

        $ipnums = explode(".", $ipr);
        if (count($ipnums) != 4) {
            return false;
        }
        foreach ($ipnums as $ipnum) {
            if (!is_numeric($ipnum) || ($ipnum > 255)) {
                return false;
            }
        }
        return $ipr;
    }

    public static function validateDomain($domain) {

        $domainr = str_replace(self::$special_chars, "", $domain);

        if (!preg_match("/^(([a-zA-Z]{1})|([a-zA-Z]{1}[a-zA-Z]{1})|([a-zA-Z]{1}[0-9]{1})|([0-9]{1}[a-zA-Z]{1})|([a-zA-Z0-9][a-zA-Z0-9-_]{1,61}[a-zA-Z0-9]))\.([a-zA-Z]{2,6}|[a-zA-Z0-9-]{2,30}\.[a-zA-Z]{2,3})$/", $domainr)) {
            return false;
        }
        return $domainr;
    }

    public static function replaceSpecialChars($host) {
        return str_replace(self::$special_chars, "", $host);
    }

    public static function getMemcache() {
        static $memcache = null;
        if (null === $memcache) {
            $memcache = new Memcache;
            $memcache->connect('localhost', 11211);
        }
        return $memcache;
    }

}
