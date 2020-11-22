<?php
/**
 * Created by PhpStorm.
 * User: LH
 * Date: 2018/4/20
 * Time: 1:19
 */

namespace framework;


class Loader
{

    // 注册自动加载机制
    public static function register($autoload = '')
    {
        // 注册系统自动加载
        spl_autoload_register($autoload?:'self::autoload', true, true);
    }

    //自动加载
    public static function autoload($class)
    {
        $lib_path = __DIR__ . DIRECTORY_SEPARATOR;
        $root_path = $lib_path.'../../';
        $name = strstr($class, '\\', true);
        if (in_array($name, ['framework']) || is_dir($lib_path . $name)) {
            $class = str_replace('framework\\', '', $class);
            $file = str_replace('\\', '/', $lib_path . $class . '.php');
        } else {
//            $class=str_replace('app\\', 'application\\',$class);
            $file = str_replace('\\', '/', $root_path . $class . '.php');
        }
        if (is_file($file)) {
            include $file;
        } else {
            echo "file {$file} is not find !";
            echo "<pre>";
            echo debug_print_backtrace();
//            $e = new \Exception;
//            echo $e->getTraceAsString();

            echo "</pre>";
            die();
        }
    }
}