<?php

require_once "vendor/Slim/Slim.php";
require_once "lib/AuthMiddleware.php";
require_once "lib/API.php";

use \Slim\Slim;
//use \AuthMiddleware;
use \API;

Slim::registerAutoloader();

$app = new Slim();
$app->contentType("Content-Type: application/json; charset=utf-8");
//$app->add(new AuthMiddleware());

$app->get("/dns/:host", "getDNSRecords");
$app->get("/whois/:host", "getWhoisLookup");
$app->get("/scan/", "scanPorts");
$app->get("/locate/:host", "getInfo");
$app->get("/ping/:host", "getPing");
$app->get("/ping/:host/:count", "getPingWithCount");
$app->get("/resolveip/:ip", "resolveIP");
$app->get("/resolvedomain/:domain", "resolveDomain");
$app->get("/myip", "getMyIPw");

$app->error(function (\Exception $e) use ($app) {
    $app->status(200);
    echo json_encode(array("status" => "failure", "error" => $e->getMessage()));
});

$app->config("debug", false);
$app->run();

function getDNSRecords($host) {
    if (!API::validateHost($host)) {
        throw new Exception("please provide valid hostname for DNS lookup.");
    }
    $memcache = API::getMemcache();
    if ($memcache->get("dns" . $host)) {
        echo $memcache->get("dns" . $host);
    } else {
        $hostname = API::validateHost($host);
        $result = json_encode(array("status" => "success", "data" => API::getDNSRecords($hostname)), JSON_PRETTY_PRINT);
        $memcache->set("dns" . $host, $result, false, 5*60);
        echo $result;
    }
}

function getWhoisLookup($host) {
    if (API::validateIP($host)) {
        throw new Exception("please provide valid hostname for WHOIS lookup.");
    }
    $memcache = API::getMemcache();
    if ($memcache->get("whois" . $host)) {
        echo $memcache->get("whois" . $host);
    } else {
        $hostname = API::validateHost($host);
        $result = json_encode(array("status" => "success", "data" => API::getWhoisLookup($hostname)), JSON_PRETTY_PRINT);
        $memcache->set("whois" . $host, $result, false, 5*60);
        echo $result;
    }
}

function scanPorts() {
    $request = Slim::getInstance()->request();
    $host = stripcslashes($request->params("host"));
    $query = stripcslashes($request->params("query"));
    
    $memcache = API::getMemcache();
    if ($memcache->get("scan" . $query . $host)) {
        echo $memcache->get("scan" . $query . $host);
    } else {
        $hostname = API::validateHost($host);
        if($query != null){
            $result = json_encode(array("status" => "success", "data" => API::scanPorts($hostname, $query)), JSON_PRETTY_PRINT);
        }else{
            $result = json_encode(array("status" => "success", "data" => API::scanCommonPorts($hostname)), JSON_PRETTY_PRINT);
        }
        $memcache->set("scan" . $query . $host, $result, false, 60);
        echo $result;
    }
}

function scanCommonPorts($host) {
    $memcache = API::getMemcache();
    if ($memcache->get("scanc" . $host)) {
        echo $memcache->get("scanc" . $host);
    } else {
        $hostname = gethostbyname(API::validateHost($host));
        $result = json_encode(array("status" => "success", "data" => API::scanCommonPorts($hostname)), JSON_PRETTY_PRINT);
        $memcache->set("scanc" . $host, $result, false, 60);
        echo $result;
    }
}

function getInfo($host) {
    $memcache = API::getMemcache();
    if ($memcache->get("info" . $host)) {
        echo $memcache->get("info" . $host);
    } else {
        $hostname = API::validateHost($host);
        $data = API::getInfo($hostname);
        $result = json_encode(array("status" => "success", "data" => $data), JSON_PRETTY_PRINT);
        $memcache->set("info" . $host, $result, false, 5*60);
        echo $result;
    }
}

function getPingWithCount($host, $count) {
    $memcache = API::getMemcache();
    if ($memcache->get("ping" . $count . $host)) {
        echo $memcache->get("ping" . $count . $host);
    } else {
        $hostname = API::validateHost($host);
        $result = json_encode(array("status" => "success", "data" => API::getPing($hostname, $count)), JSON_PRETTY_PRINT);
        $memcache->set("ping" . $count . $host, $result, false, 60);
        echo $result;
    }
}

function getPing($host) {
    $memcache = API::getMemcache();
    if ($memcache->get("ping" . $host)) {
        echo $memcache->get("ping" . $host);
    } else {
        $hostname = API::validateHost($host);
        $result = json_encode(array("status" => "success", "data" => API::getPing($hostname, 5)), JSON_PRETTY_PRINT);
        $memcache->set("ping" . $host, $result, false, 60);
        echo $result;
    }
}

function resolveIP($ip) {
    $ip = API::replaceSpecialChars($ip);
    if (!API::validateIP($ip)) {
        throw new Exception("please provide valid IP address for resolution.");
    }
    $memcache = API::getMemcache();
    if ($memcache->get("ip" . $ip)) {
        echo $memcache->get("ip" . $ip);
    } else {
        $result = json_encode(array("status" => "success", "data" => array("domain" => gethostbyaddr($ip))), JSON_PRETTY_PRINT);
        $memcache->set("ip" . $ip, $result, false, 60);
        echo $result;
    }
}

function resolveDomain($domain) {
    $domain = API::replaceSpecialChars($domain);
    if (!API::validateDomain($domain)) {
        throw new Exception("please provide valid domain for resolution.");
    }
    $memcache = API::getMemcache();
    if ($memcache->get("domain" . $domain)) {
        echo $memcache->get("domain" . $domain);
    } else {
        $result = json_encode(array("status" => "success", "data" => array("ip" => gethostbynamel($domain))), JSON_PRETTY_PRINT);
        $memcache->set("domain" . $domain, $result, false, 60);
        echo $result;
    }
}

function getMyIPw() {
    $ip = "";
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } else {
        $ip = $_SERVER["REMOTE_ADDR"];
    }
    if(strpos($ip, ",") !== false){
        $ip = explode(",", $ip)[0];
    }
    $result = json_encode(array("status" => "success", "data" => array("ip" => $ip)), JSON_PRETTY_PRINT);
    echo $result;
}
