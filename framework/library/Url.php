<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 14:34
 */

namespace framework;


class Url
{

    /**
     * @param $path
     * @param null $vars
     * @return string
     */
    public static function parseUrl($path, $vars = null)
    {
        $app = App::getInstance();

        $module_name = $app::$module;
        $controller_name = $app::$controller;

        $arr_path = array_filter(explode('/', $path));
        $count = count($arr_path);

        $url = "";

        if ($count == 1) {
            $url = '?s=' . $module_name . '/' . $controller_name . '/' . $path;
        }
        if ($count == 2) {
            list($controller, $method) = $arr_path;
            $url = '?s=' . $module_name . '/' . $controller . '/' . $method;
        }
        if ($count == 3) {
            list($module, $controller, $method) = $arr_path;
            $url = '?s=' . $module . '/' . $controller . '/' . $method;
        }
        if (!empty($vars)) {
            if (is_array($vars)) {
                $url .= '&' . http_build_query($vars);
            }
            if (is_string($vars)) {
                $url .= '&' . $vars;
            }
        }
        return $url;
    }

}