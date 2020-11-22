<?php
/**
 * Created by PhpStorm.
 * User: LH
 * Date: 2018/4/20
 * Time: 1:07
 */


function model($model_name)
{
    $class_name = ucfirst($model_name) . 'Model';
    $class_name = "app\\{$model_name}\\model\\{$class_name}";
    return new $class_name();

}

function json($val)
{
    header("Content-Type:text/html; charset=utf-8");
    exit(json_encode($val));
}


function url($path, $vars = null)
{
    \framework\Url::parseUrl($path,$vars);
}

function cache($key, $val = null)
{
    $cache = \framework\Cache::instance();
    if (!isset($val)) {
        return $cache->get($key);
    } else {
        $cache->set($key, $val);
    }
}

function session($key, $val = null)
{
   \framework\Session::get($key,$val);
}

function cookie($key, $val = null)
{
    $cookie_prefix = config('cookie_prefix');
    $key = $cookie_prefix . $key;
    if (isset($val)) {
        $_COOKIE[$key] = $val;
    } else {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        } else {
            return false;
        }
    }
}

function config($key, $val = null)
{
    if (!isset($val)) {
        return \framework\Config::get($key);
    } else {
        \framework\Config::set($val, $val);
    }
}


function input($key = '', $default = null, $filter = '')
{
    if (isset($_REQUEST[$key])) {

        $value = $_REQUEST[$key];

    } elseif (isset($_POST[$key])) {

        $value = $_POST[$key];

    } elseif (isset($_GET[$key])) {

        $value = $_GET[$key];

    } else {
        $value = $default;
    }
    return empty($filter) ? $value : $filter($value);
}


function redirect($url)
{
    header('Location: ' . $url);
}


/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip[$type];
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos)
                unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}



