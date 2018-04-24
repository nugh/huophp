<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 21:05
 */

namespace framework;


class Route
{

  protected static $routers = [];
    public static function addRule($rule, $path, $method='GET'){
        preg_match_all('/\{\$([\w]+)\}/is', $rule, $arr);
        self::$routers[] = [
            'route'=>$rule,
            'preg' => '/^' . preg_replace('/\\\{\\\\\$[\w]+\\\}/', '([\w]+)', preg_quote($rule, '/')) . '$/is',
            'path'=>$path,
            'search'=>$arr[0],
            'param'=>$arr[1],
            'method'=>$method
        ];
    }
    public static function parseUrl($url){
        if ($ext = pathinfo($url, PATHINFO_EXTENSION)) {
            $url = mb_substr($url, 0, -mb_strlen($ext)-1);
        }
        foreach (self::$routers as $router) {
            if (preg_match($router['preg'], $url, $arr)) {
                array_shift($arr);
                return [
                    'path'=>$router['path'],
                    'param'=>array_combine($router['param'], $arr)
                ];
            }
        }
        return false;
    }
    public static function buildUrl($path, $param, $method='GET'){
        foreach (self::$routers as $value) {
            if ($value['method'] == $method && $value['path'] == $path && $value['param'] == array_keys($param)) {
                return str_replace(array_values($value['search']), $param, $value['route']);
            }
        }
        return $path . '/' . implode('/', $param);
    }
	
}