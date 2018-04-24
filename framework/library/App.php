<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 12:05
 */

namespace framework;


class App
{

    public static $module;
    public static $controller;
    public static $method;

    /**
     * 执行应用程序
     */
    public static function run()
    {
        //设置调试模式
        if (!Config::get('app_debug')) {
            ini_set('display_errors', 'Off');
        } elseif (PHP_SAPI != 'cli') {
            //重新申请一块比较大的buffer
            if (ob_get_level() > 0) {
                $output = ob_get_clean();
            }
            ob_start();
            if (!empty($output)) {
                echo $output;
            }
        }

        //设置时区
        date_default_timezone_set('Asia/Shanghai');

        //执行
        self::exec();
    }



    //解析url
    public static function exec()
    {
        $param = isset($_GET['s']) ? $_GET['s'] : '';
        $param = str_ireplace('.php', '', $param);
        $arr = explode('/', trim($param, '/'));
        $module = preg_replace("/[^0-9a-z_]/i", '', isset($arr[0]) ? $arr[0] : '');
        $controller = preg_replace("/[^0-9a-z_]/i", '', isset($arr[1]) ? $arr[1] : '');
        $action = preg_replace("/[^0-9a-z_]/i", '', isset($arr[2]) ? $arr[2] : '');

        if (empty($module)) $module = "index";
        if (empty($controller)) $controller = "Index";
        if (empty($action)) $action = "index";

        //记录着当前模块，控制器，方法名称
        $module=strtolower($module);
        $controller=ucfirst($controller);
        $action=strtolower($action);

        define('MODULE_NAME',$module);
        define('CONTROLLER_NAME',$controller);
        define('METHOD_NAME',$action);

        if (in_array($module, array('common'))) {//禁止访问的模块
            exit("module {$module} is not find !");
        }
        $classname = "app\\{$module}\\controller\\{$controller}Controller";
        $reflectionClass = new \ReflectionClass($classname);
        $cla = $reflectionClass->newInstance();
        if (method_exists($cla, $action)) {
            $method = $reflectionClass->getmethod($action);
            if ($method -> getNumberOfParameters() > 0) {
                $vars = $_REQUEST;
                $params = $method -> getParameters();
                $args = array();
                foreach ($params as $param) {
                    $name = $param -> getName();
                    if (isset($vars[$name])) {
                        $args[] = $vars[$name];
                    } elseif ($param -> isDefaultValueAvailable()) {
                        $args[] = $param -> getDefaultValue();
                    } else {
                        exit('缺少' . $name . '参数');
                    }
                }
                return $method -> invokeArgs($cla, $args);
            } else {
                return $method -> invoke($cla);
            }
        } else {
            exit("method {$action} is not find !");
        }
    }

}