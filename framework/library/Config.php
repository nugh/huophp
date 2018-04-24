<?php
/**
 * Created by PhpStorm.
 * User: LH
 * Date: 2018/4/20
 * Time: 0:18
 */

namespace framework;


class Config
{
    public static $data = array();

    function __set($key, $val) {
        self::set($key,$val);
    }

    function __get($key) {
        return self::get($key);
    }

    public static function set($key, $val=null) {
        if (is_array($key)){
            self::$data=array_merge(self::$data,$key);
        }else{
            self::$data[$key] = $val;
        }
    }

    public static function get($key,$val=null) {
        if (isset(self::$data[$key])) {
            return self::$data[$key];
        } else {
            return $val;
        }
    }

    public static function has($key) {
        return array_key_exists($key, self::$data);
    }
}