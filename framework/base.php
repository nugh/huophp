<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 10:11
 */

namespace framework;

define('FRAME_VERSION', '0.1');
define('DS', DIRECTORY_SEPARATOR);
defined('FRAME_PATH') or define('FRAME_PATH', __DIR__ . DS);
define('LIB_PATH', FRAME_PATH . 'library' . DS);
define('TPL_PATH', FRAME_PATH . 'tpl' . DS);
define('EXT_PATH', FRAME_PATH . 'ext' . DS);
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);
defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
defined('CONF_PATH') or define('CONF_PATH', ROOT_PATH . 'config' . DS); // 配置文件目录
define('IS_AJAX',((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_REQUEST['is_ajax'])) ? true : false);

// 载入Loader类
require LIB_PATH . 'loader.php';

// 注册自动加载
Loader::register();

// 加载惯例配置文件
Config::set(include FRAME_PATH . 'convention.php');

//载入函数文件
include FRAME_PATH . 'helper.php';

//载入自定义函数
include APP_PATH . 'common/common/common.php';

//加载用户自定义配置
Config::set(include CONF_PATH . 'config.php');

// 执行应用
App::run();