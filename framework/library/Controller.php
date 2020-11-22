<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 17:24
 */

namespace framework;


class Controller
{
    /**
     * @var View
     */
    protected $view;
    
    protected $request;

    // 构造函数，初始化属性，并实例化对应模型
    function __construct()
    {
        $this->view = View::instance();
        $this->request = Request::instance();
        $this->initialize();
    }

    // 初始化
    protected function initialize(){}

    // 分配变量
    function assign($name, $value='')
    {
        $this->view->assign($name, $value);
    }

    //渲染模板
    function fetch($template='',$module_name='')
    {
        return $this->view->fetch($template,$module_name);
    }

    //打印模板
    function display($template='',$module_name='')
    {
        return $this->view->display($template,$module_name);
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        }

        $result = [
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        if($this->request->isAjax()){
            json($result);
        }

        $this->assign($result);
        $this->fetch('_dispatch_jump');
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url)) {
            $url = $this->request->isAjax() ? '' : 'javascript:history.back(-1);';
        }

        $result = [
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        if($this->request->isAjax()){
            json($result);
        }

        $this->assign($result);
        $this->fetch('_dispatch_jump');
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param  mixed     $data 要返回的数据
     * @param  integer   $code 返回的code
     * @param  mixed     $msg 提示信息
     * @param  string    $type 返回数据格式
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function result($data, $code = 0, $msg = '', $type = '', array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => time(),
            'data' => $data,
        ];

        json($result);
    }

    /**
     * URL重定向
     * @access protected
     * @param  string         $url 跳转的URL表达式
     * @return void
     */
    protected function redirect($url)
    {
        header('Location: ' . $url);
    }

}