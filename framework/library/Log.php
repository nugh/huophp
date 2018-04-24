<?php
/**
 * Created by PhpStorm.
 * User: ZXL
 * Date: 2018/1/10
 * Time: 21:59
 */

namespace framework;


class Log
{

    /**
     * 日志直接写入
     * @static
     * @access public
     * @param string $message 日志信息
     * @return void
     */
    static function write($message,$filename=null)
    {
        if (empty($message))
            return;

        if(empty($filename)){
            $filename=date('y_m_d');
        }

        $log_file = RUNTIME_PATH . '/log/' . $filename . '.log';
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $message = date('Y-m-d H:i:s').'  '.$message. "\r\n";;
        file_put_contents($log_file,$message,FILE_APPEND);
    }

}