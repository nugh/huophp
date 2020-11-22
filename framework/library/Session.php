<?php
/**
 * Created by PhpStorm.
 * User: LH
 * Date: 2018/4/20
 * Time: 0:57
 */

namespace framework;


class Session
{

    public static function get($key, $val = null)
    {
        if ($key == null) {
            $_SESSION = array();
            return;
        }
        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($val)) {
            $_SESSION[$key] = $val;
        } else {
            if (isset($_SESSION[$key])) {
                return $_SESSION[$key];
            } else {
                return null;
            }
        }
    }
}