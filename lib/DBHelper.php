<?php

use \PDO;

class DBhelper {

    //Credentials
    public static $HOSTNAME = 'localhost';
    public static $PORT = 3306;
    public static $DBNAME = 'API_DB';
    public static $USERNAME = 'xxx';
    public static $PASSWD = 'xxx';

    //Get persistent PDO connection object    
    public static function getConn() {
        static $db = null;
        if (null === $db) {
            $db = new PDO("mysql:host=" . self::$HOSTNAME . ";dbname=" . self::$DBNAME, self::$USERNAME, self::$PASSWD, array(
                PDO::ATTR_PERSISTENT => true
            ));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $db;
    }

    public static function getService($port) {
        $sql = "SELECT * FROM `services` WHERE `protocol`=? AND `port`=?";
        $proto = "TCP";
        try {
            $db = self::getConn();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(1, $proto);
            $stmt->bindParam(2, $port);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return "";
            }
            return $row["service"];
        } catch (PDOException $e) {
            
        }
    }

    public static function getLocation($ip) {
        $sql = "SELECT * FROM `data2` WHERE (INET_ATON(?) BETWEEN INET_ATON(ip_start) AND INET_ATON(ip_end))";
        $result = array();
        try {
            $db = self::getConn();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(1, $ip, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return "";
            }
            $result["country"] = utf8_encode($row["country"]);
            $result["province"] = utf8_encode($row["province"]);
            $result["city"] = utf8_encode(str_replace("\r", "", $row["city"]));
            $db = null;
            return $result;
        } catch (PDOException $e) {
            
        }
    }

}
