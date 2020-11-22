<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 21:05
 */

namespace framework;


class Cache
{
    private static $path;

    public static $data = array();

    // 缓存实例
    protected static $instance;


    protected function __construct() {
        self::$path = App::getInstance()->getRuntimePath().'cache';
    }


    /**
     * 初始化缓存
     * @access public
     * @return self
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function set($key, $val, $expired = null) {
        self::$data[$key]['val'] = $val;
        self::$data[$key]['expired'] = $expired;
        file_put_contents(self::$path . md5($key).'.php', bin2hex(json_encode(self::$data[$key])));
    }

    public static function get($key) {
        if (!isset(self::$data[$key])) {
            if (is_file(self::$path . md5($key))) {
                $val = json_decode(hex2bin(file_get_contents(self::$path . md5($key).'.php')), true);
                return $val;
            } else {
                return false;
            }
        } else {
            $val = self::$data[$key];
        }

        if (isset($val)) {
            if (empty($val['expired'])||$val['expired'] > time()){
                return $val['val'];
            } else {
                return false;
            }
        }
    }

    public static function has($key) {
        return isset(self::$data[$key]);
    }
}