<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 12:05
 */

namespace framework;


class App extends Container
{

    const VERSION = '0.0.1';

    /**
     * 应用根目录
     * @var string
     */
    protected $rootPath = '';

    /**
     * 框架目录
     * @var string
     */
    protected $frameworkPath = '';

    /**
     * 应用目录
     * @var string
     */
    protected $appPath = '';

    /**
     * Runtime目录
     * @var string
     */
    protected $runtimePath = '';

    public static $module;
    public static $controller;
    public static $method;

    /**
     * 架构方法
     * @access public
     */
    public function __construct()
    {
        $this->frameworkPath   = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $this->rootPath    = dirname($this->frameworkPath) . DIRECTORY_SEPARATOR;
        $this->appPath     = $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
        $this->runtimePath = $this->rootPath . 'runtime' . DIRECTORY_SEPARATOR;

        static::setInstance($this);
    }


    /**
     * 获取框架版本
     * @access public
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * 获取应用根目录
     * @access public
     * @return string
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * 获取应用基础目录
     * @access public
     * @return string
     */
    public function getBasePath()
    {
        return $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取当前应用目录
     * @access public
     * @return string
     */
    public function getAppPath()
    {
        return $this->appPath;
    }

    /**
     * 设置应用目录
     * @param string $path 应用目录
     */
    public function setAppPath($path)
    {
        $this->appPath = $path;
    }

    /**
     * 获取应用运行时目录
     * @access public
     * @return string
     */
    public function getRuntimePath()
    {
        return $this->runtimePath;
    }

    /**
     * 设置runtime目录
     * @param string $path 定义目录
     */
    public function setRuntimePath($path)
    {
        $this->runtimePath = $path;
    }

    /**
     * 获取核心框架目录
     * @access public
     * @return string
     */
    public function getFrameworkPath()
    {
        return $this->frameworkPath;
    }

    /**
     * 获取应用配置目录
     * @access public
     * @return string
     */
    public function getConfigPath()
    {
        return $this->rootPath . 'config' . DIRECTORY_SEPARATOR;
    }

    /**
     * 执行应用程序
     */
    public function run()
    {

        //初始化
        $this->initialize();

        //执行
        self::exec();

        //重新申请一块比较大的buffer
        if (ob_get_level() > 0) {
            $output = ob_get_clean();
        }
        ob_start();
        if (!empty($output)) {
            echo $output;
        }

    }


    /**
     * 初始化
     */
    public function initialize()
    {

        // 加载全局初始化文件
        $this->load();

        //加载用户自定义配置
        Config::set(include $this->getConfigPath() . 'config.php');

        date_default_timezone_set( 'Asia/Shanghai');
    }


    /**
     * 加载应用文件和配置
     * @access protected
     * @return void
     */
    protected function load()
    {
        $appPath = $this->appPath;

        if (is_file($appPath . 'common.php')) {
            include_once $appPath . 'common.php';
        }

        include_once $this->frameworkPath . 'helper.php';

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

        self::$module=$module;
        self::$controller=$controller;
        self::$method=$action;

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