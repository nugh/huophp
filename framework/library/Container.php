<?php


namespace framework;


class Container
{

    /**
     * 容器对象实例
     * @var Container
     */
    protected static $instance;

    /**
     * 获取当前容器的实例（单例）
     * @access public
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

//        if (static::$instance instanceof Closure) {
//            return (static::$instance)();
//        }

        return static::$instance;
    }

    /**
     * 设置当前容器的实例
     * @access public
     * @param object $instance
     * @return void
     */
    public static function setInstance($instance)
    {
        static::$instance = $instance;
    }

}