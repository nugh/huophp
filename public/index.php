<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 10:09
 */

namespace framework;

// 载入Loader类
require __DIR__ . '/../framework/autoload.php';

// 执行应用
(new App)-> run();

