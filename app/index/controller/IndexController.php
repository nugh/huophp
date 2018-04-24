<?php
/**
 * Created by PhpStorm.
 * User: LH
 * Date: 2018/4/24
 * Time: 10:18
 */

namespace app\index\controller;

use app\common\controller\IndexController as HomeController;

class IndexController extends HomeController
{

    public function index()
    {
        return $this->fetch();
    }

}