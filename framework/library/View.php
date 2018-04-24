<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 17:32
 */

namespace framework;


class View
{

    // 视图实例
    protected static $instance;

    // 模板变量
    protected $data = [];

    protected $module;
    protected $controller;
    protected $method;

    //编译的文件目录
    protected $compile_dir;

    function __construct()
    {
        $this->module = MODULE_NAME;
        $this->controller = CONTROLLER_NAME;
        $this->method = METHOD_NAME;
        $this->compile_dir = RUNTIME_PATH . 'temp';
    }

    /**
     * 初始化视图
     * @access public
     * @return self
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * 模板变量赋值
     * @access public
     * @param mixed $name
     * @param mixed $value
     * @return void
     */
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
    }

    public function display($template = '', $module_name = '')
    {
        if (strpos($template, '_dispatch_jump') !== false) {
            $tpl_file = TPL_PATH.'dispatch_jump.tpl';
        } else {
            if (empty($template)) {
                $template = $this->method;
            }
            if (empty($module_name)) {
                $module_name = $this->module;
            }
            $tpl_file = APP_PATH . $module_name . DS . 'view' . DS . strtolower($this->controller) . DS . $template . '.html';
        }

        //模板文件
        if (!file_exists($tpl_file)) {
            exit("file {$tpl_file} is not find !");
        }

        //编译文件
        $parse_file = $this->parse($tpl_file);

        ob_start();
        ob_implicit_flush(0);
        extract($this->data, EXTR_OVERWRITE);
        //载入编译文件
        include $parse_file;
        $content = ob_get_clean();
        return $content;
    }

    public function fetch($template = '', $module_name = '')
    {
        if (strpos($template, '_dispatch_jump') !== false) {
            $tpl_file = TPL_PATH.'dispatch_jump.tpl';
        } else {
            if (empty($template)) {
                $template = $this->method;
            }
            if (empty($module_name)) {
                $module_name = $this->module;
            }
            $tpl_file = APP_PATH . $module_name . DS . 'view' . DS . strtolower($this->controller) . DS . $template . '.html';
        }

        //模板文件
        if (!file_exists($tpl_file)) {
            exit("file {$tpl_file} is not find !");
        }

        //编译文件
        $parse_file = $this->parse($tpl_file);

        extract($this->data, EXTR_OVERWRITE);

        //载入编译文件
        return include $parse_file;
    }


    //模板编译
    private function parse($tpl_file)
    {
        $parse_file = $this->compile_dir . '/' . md5_file($tpl_file) . '.php';

        if (!file_exists($parse_file) || filemtime($parse_file) < filemtime($tpl_file) || Config::get('app_debug')) {

            $content = file_get_contents($tpl_file);

            $content = $this->_parse_layout($content);

            $content = $this->_parse_sentence($content);

            $content = $this->_parse_var($content);

            //编译完成后，生成编译文件
            if (!file_put_contents($parse_file, $content)) {
                exit('编译文件生成出错了');
            }
        }
        return $parse_file;
    }


    private function _parse_layout($content)
    {
        $arr_content = explode("\n", $content);
        $fisrt_line = array_shift($arr_content);
        $new = array();

        if (strpos($fisrt_line, '<layout') !== false) {
            $pattern = '/<layout\s{1,}name=[\'|"]([\w\d]+)[\'|"].*\/\>/';
            $layout_name = preg_replace($pattern, '$1', $fisrt_line);
        } else {
            return $content;
        }

        foreach ($arr_content as $key => $val) {
            if (strpos($val, '<block') !== false) {
                $pattern = '/<block[\s*]name=[\'|"]([\w\d]+)[\'|"].*\>/';
                preg_match($pattern, $val, $matches);
                $block_name = $matches[1];
            } else {
                if (strpos($val, '</block') === false) {
                    if (isset($new[$block_name])) {
                        $new[$block_name] .= $val . "\n";
                    } else {
                        $new[$block_name] = $val . "\n";
                    }
                }
            }
        }

        $layout_content = file_get_contents(ROOT_PATH . './layout/' . trim($layout_name) . '.html');

        $arr_layout_content = explode("\n", $layout_content);

        foreach ($arr_layout_content as $key => &$val) {
            if (strpos($val, '<block') !== false) {
                $pattern = '/\s{0,}\t{0,}<block\s{1,}name=[\'|"]([\w\d]+)[\'|"].*\><\/block>/';
                preg_match($pattern, $val, $matches);
                $block_name = $matches[1];
                if (!empty($new[$block_name])) {
                    $val = preg_replace($pattern, $new[$block_name], $val);
                }
            }
        }
        return implode("\n", $arr_layout_content);
    }

    private function _parse_sentence($content)
    {
        $arr_content = explode("\n", $content);
        $new = array();
        //print_r($arr_content);
        foreach ($arr_content as $key => &$val) {
            if (strpos($val, '<foreach') !== false) {
                $pattern = '/<foreach\s{1,}name=[\'|"]([\w\d]+)[\'|"][^\f\n\r\t\v]*\>/';
                preg_match($pattern, $val, $matches);
                if (!empty($matches)) {
                    $vo_name = $matches[1];
                }

                $pattern = '/<foreach[^\f\n\r\t\v]*key=[\'|"]([\w\d]+)[\'|"][^\f\n\r\t\v]*\>/';
                preg_match($pattern, $val, $matches);

                if (!empty($matches)) {
                    $key_name = $matches[1];
                } else {
                    $key_name = 'key';
                }

                $pattern = '/<foreach[^\f\n\r\t\v]*val=[\'|"]([\w\d]+)[\'|"][^\f\n\r\t\v]*\>/';
                preg_match($pattern, $val, $matches);
                if (!empty($matches)) {
                    $val_name = $matches[1];
                } else {
                    $val_name = 'val';
                }
                if (empty($this->data[$vo_name])) {
                    $this->data[$vo_name] = array();
                }

                $pattern = '/<foreach[^\f\n\r\t\v]*\>/';
                $php = '<?php if(!empty($' . $vo_name . ')){foreach($' . $vo_name . ' as $' . $key_name . '=>$' . $val_name . '){ ?>';
                $val = preg_replace($pattern, $php, $val);
            }
            if (strpos($val, '</foreach>') !== false) {
                $php = '<?php }} ?>';
                $val = str_replace('</foreach>', $php, $val);
            }

            if (strpos($val, '<if ') !== false) {
                $pattern = '/<if\s{1,}condition=[\'|"]([^\f\n\r\t\v]*)[\'|"]\s{0,}\>/';
                preg_match($pattern, $val, $matches);
                if (!empty($matches)) {
                    $condition = $matches[1];
                }

                $php = '<?php if(' . $condition . '){ ?>';
                $val = preg_replace($pattern, $php, $val);
            }
            if (strpos($val, '</if>') !== false) {
                $php = '<?php } ?>';
                $val = str_replace('</if>', $php, $val);
            }
            if (strpos($val, '<else/>') !== false) {
                $php = '<?php }else{ ?>';
                $val = str_replace('<else/>', $php, $val);
            }
        }
        return implode("\n", $arr_content);
    }

    //解析普通变量，如把{$name}解析成$this->data['name']
    private function _parse_var($content)
    {

        //{$var}格式替换
        $pattern = '/\{\$([\w\d]+)\}/';
        if (preg_match($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '<?php echo(isset($$1)?$$1:\'\'); ?>', $content);
        }

        //{$var.key]}格式替换
        $pattern = '/\{\$([\w\d]+)\.([\w\d]+)\}/';
        if (preg_match($pattern, $content, $a)) {
            $content = preg_replace($pattern, '<?php echo(isset($$1[\'$2\'])?$$1[\'$2\']:\'\'); ?>', $content);
        }

        //{$var['key']}格式替换
        $pattern = '/\{\$([\w\d]+\[[\'|"][\w\d]+[\'|"]\])\}/';
        if (preg_match($pattern, $content, $a)) {
            $content = preg_replace($pattern, '<?php echo(isset($$1)?$$1:\'\'); ?>', $content);
        }

        //{$var['key']['key']}格式替换
        $pattern = '/\{\$([\w\d]+\[[\'|"][\w\d]+[\'|"]\]\[[\'|"][\w\d]+[\'|"]\])\}/';
        if (preg_match($pattern, $content, $a)) {
            $content = preg_replace($pattern, '<?php echo(isset($$1)?$$1:\'\'); ?>', $content);
        }

        //{:func()}格式替换
        $pattern = '/\{\:(.*?)\}/';
        if (preg_match($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '<?php echo($1); ?>', $content);
        }

        //<php>格式替换
        $pattern = '/<php>/';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '<?php ', $content);
        }

        //</php>格式替换
        $pattern = '/<\/php>/';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '?>', $content);
        }

        //</php>格式替换
        $content = str_replace('__SELF__', $_SERVER['REQUEST_URI'], $content);

        return $content;
    }
}