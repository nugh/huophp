<?php

namespace app\common\model;

use framework\Model;

class BaseModel extends Model {
    protected $_auto = array('create_time' => 'get_create_time', 'user_id' => 'get_user_id', 'user_name' => 'get_user_name', 'dept_id' => 'get_dept_id', 'dept_name' => 'get_dept_name');

    protected function get_create_time() {
        return time();
    }

    protected function get_user_id() {
        return get_user_id();
    }

    protected function get_user_name() {
        return get_user_name();
    }

    protected function get_dept_id() {
        return get_dept_id();
    }

    protected function get_dept_name() {
        return get_dept_name();
    }

}
