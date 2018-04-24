<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 17:28
 */

namespace framework;


class Model extends Db
{

    protected $name;
    protected $table_name;
    protected $alias;
    protected $fields;

    protected $db;
    protected $table_prefix;
    protected $data;
    protected $last_sql;

    protected $options;

    protected $_filter;
    protected $_auto;
    protected $_validate;

    protected $error;


    function __construct($model_name = null)
    {
        parent::__construct($model_name);

        if (empty($model_name)) {
            $model_name = get_class($this);
            $model_name=substr($model_name,strrpos($model_name,'\\')+1);
            $this -> name = strtolower(str_replace('Model', '', $model_name));
        } else {
            $this -> name = $model_name;
        }

        // 数据库表名与类名一致
        $this->table_name = strtolower($this->table_prefix.$this->name);
    }

    public function __set($key, $val) {
        // 设置数据对象属性
        $this -> data[$key] = $val;
    }

    public function __get($key) {
        return isset($this -> data[$key]) ? $this -> data[$key] : null;
    }

    function select($select) {
        $this -> options['select'] = $select;
        return $this;
    }

    function from($from) {
        $this -> options['from'] = $from;
        return $this;
    }

    function left($left, $on) {
        $this -> options['join'][] = array('left', $left, $on);
        return $this;
    }

    function inner($inner, $on) {
        $this -> options['join'][] = array('inner', $inner, $on);
        return $this;
    }

    function where($where) {
        if (isset($this -> options['where'])) {
            $this -> options['where'] = array_merge($this -> options['where'], $where);
        } else {
            $this -> options['where'] = $where;
        }
        return $this;
    }

    function limit($start, $nums) {
        $this -> options['limit'] = array($start, $nums);
        return $this;
    }

    function group($group) {
        $this -> options['group'] = $group;
        return $this;
    }

    function order($order) {
        $this -> options['order'] = $order;
        return $this;
    }

    function find($id = null) {
        if (!empty($id)) {
            $where[] = array('id', 'eq', $id);
            $this -> where($where);
        }
        $this -> limit(0, 1);

        $vo = $this -> get_list();

        if (isset($this -> options['parm'])) {
            $parm = $this -> options['parm'];
        } else {
            $parm = array();
        }

        $this -> options = array();

        if (!empty($vo)) {
            return $vo[0];
        } else {
            return false;
        }
    }

    function get_list() {
        $select = $distinct = $from = $inner = $where = $limit = $group = $order = '';
        if (isset($this -> options['select'])) {
            if (strpos($this -> options['select'], 'distinct') !== false) {
                $distinct = 'distinct ';
            }
            $fields = explode(',', str_replace('distinct', '', $this -> options['select']));
        } else {
            $fields = array('*');
        }

        $select .= 'select ' . $distinct;
        foreach ($fields as $val) {
            $val = trim($val);
            if (strpos($val, '.*') !== false) {
                list($table, $field) = explode('.', $val);
                $select .= $table . ".`" . implode('`,' . $table . '.`', $this -> get_db_fields($table)) . "`,";
                continue;
            }
            //select *
            if (strpos($val, '*') !== false) {
                $select .= $this -> name . ".`" . implode('`,' . $this -> name . '.`', $this -> get_db_fields($this -> name)) . "`,";
                continue;
            }
            //select sum(field)
            if (strpos($val, 'sum(') !== false) {
                $select .= $val . ',';
                continue;
            }
            //select table.field
            if (strpos($val, '.') !== false) {
                list($table, $field) = explode('.', $val);
                if (strpos($field, ' ') !== false) {
                    list($a, $b) = explode(' ', $field);
                    $select .= $table . '.`' . $a . '` `' . $b . '`,';
                    ;
                } else {
                    $select .= $table . '.`' . $field . '`,';
                }
                continue;
            }

            $select .= '`' . $val . '`,';
        }
        //去掉最后,
        $select = substr($select, 0, -1);

        //from
        if (isset($this -> options['from'])) {
            $from = 'from ';
            $tables = array_filter(explode(',', $this -> options['from']));
            foreach ($tables as $val) {
                $val = trim($val);
                if (strpos($val, ' ') !== false) {
                    list($table, $alias) = explode(' ', $val);
                    $this -> alias[] = $alias;
                    $from .= $this -> table_prefix . $table . ' `' . $alias . '`,';
                } else {
                    $this -> alias[] = $val;
                    $from .= $this -> table_prefix . $val . ' `' . $val . '`,';
                }
            }
            $from = substr($from, 0, -1);
        } else {
            $this -> alias[] = $this -> name;
            $from = 'from ' . $this -> table_name . ' `' . $this -> name . '`';
            ;
        }

        //join
        $join = '';
        if (isset($this -> options['join'])) {
            foreach ($this -> options['join'] as $val) {
                list($type, $table, $on) = $val;
                if (strpos($table, ' ') !== false) {
                    list($table, $alias) = explode(' ', $table);
                } else {
                    $alias = $table;
                }
                $this -> alias[] = $alias;
                $join .= ' ' . $type . ' join ' . $this -> table_prefix . $table . ' `' . $alias . '` on ' . $on . ' ';
            }
        }

        //where
        if (!empty($this -> options['where'])) {
            $where = ' where 1=1 ';
            foreach ($this -> options['where'] as $val) {
                $where .= $this -> _build_query_string($val);
            }
        } else {
            $where = ' where 1=1 ';
        }

        //limit
        if (isset($this -> options['limit'])) {
            list($start, $nums) = $this -> options['limit'];
            $limit = " limit {$start},{$nums} ";
        }

        //group
        if (isset($this -> options['group'])) {
            $group = 'group by ' . $this -> options['group'];
        }

        //order
        if (isset($this -> options['order'])) {
            $order = 'order by ' . $this -> options['order'];
        }

        $sql = $select . ' ' . $from . ' ' . $join . ' ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit;
        $this -> last_sql = $sql;

        if (isset($this -> options['parm'])) {
            $parm = $this -> options['parm'];
        } else {
            $parm = array();
        }
        $this -> options = array();
        //echo $sql;

        return $this  -> query($sql, $parm);
    }

    function count($condition = '*') {
        $select = $from = $inner = $where = $limit = $group = $order = '';
        //select
        $select = 'select count(' . $condition . ') xcount ';
        //from
        if (isset($this -> options['from'])) {
            $from = 'from ';
            $tables = array_filter(explode(',', $this -> options['from']));
            foreach ($tables as $val) {
                $val = trim($val);
                if (strpos($val, ' ') !== false) {
                    list($table, $alias) = explode(' ', $val);
                    $this -> alias[] = $alias;
                    $from .= $this -> table_prefix . $table . ' ' . $alias . ',';
                } else {
                    $this -> alias[] = $val;
                    $from .= $this -> table_prefix . $val . ' ' . $val . ',';
                }
            }
            $from = substr($from, 0, -1);
        } else {
            $this -> alias[] = $this -> name;
            $from = 'from ' . $this -> table_name . ' ' . $this -> name;
        }

        //join
        $join = '';
        if (isset($this -> options['join'])) {
            foreach ($this -> options['join'] as $val) {
                list($type, $table, $on) = $val;
                if (strpos($table, ' ') !== false) {
                    list($table, $alias) = explode(' ', $table);
                } else {
                    $alias = $table;
                }
                $this -> alias[] = $alias;
                $join .= ' ' . $type . ' join ' . $this -> table_prefix . $table . ' ' . $alias . ' on ' . $on . ' ';
            }
        }

        //where
        if (!empty($this -> options['where'])) {
            $where = ' where 1=1 ';
            foreach ($this -> options['where'] as $val) {
                $where .= $this -> _build_query_string($val);
            }
        } else {
            $where = ' where 1=1 ';
        }

        //limit
        if (isset($this -> options['limit'])) {
            list($start, $nums) = $this -> options['limit'];
            $limit = " limit {$start},{$nums} ";
        }

        //group
        if (isset($this -> options['group'])) {
            $group = 'group by ' . $this -> options['group'];
        }

        //order
        if (isset($this -> options['order'])) {
            $order = 'order by ' . $this -> options['order'];
        }

        $sql = $select . ' ' . $from . ' ' . $join . ' ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit;
        $this -> last_sql = $sql;

        if (isset($this -> options['parm'])) {
            $parm = $this -> options['parm'];
        } else {
            $parm = array();
        }
        $this -> options = array();
        $vo = $this  -> query($sql, $parm);

        if (!empty($vo)) {
            return $vo[0]['xcount'];
        } else {
            return '0';
        }
    }

    function create($data = null) {

        if (empty($data)) {
            $this -> data = array_filter($_REQUEST, array($this, '_filter_request'));
        } else {
            $this -> data = array_filter($data, array($this, '_filter_request'));
        }

        $this -> fields = $this -> get_db_fields();
        if (!empty($this -> _auto)) {
            foreach ($this->_auto as $key => $val) {
                if (is_callable(array($this, $val))) {
                    $this -> data[$key] = $this -> $val();
                }
            }
        }

        foreach ($this -> data as $key => $val) {
            if (in_array($key, $this -> fields)) {
                $this -> data[$key] = $this -> filter($key, $val);
            } else {
                unset($this -> data[$key]);
            }
        }
        return $this -> data;
    }

    protected function _before_add(&$data) {
    }

    protected function _after_add(&$data) {
    }

    function add($data = null) {
        if (empty($data)) {
            $data = $this -> data;
        }
        $this -> _before_add($data);
        $sql = 'insert into ' . $this -> table_name;
        $keys = array_keys($data);
        $sql .= ' (`' . implode('`,`', $keys) . '`) values (:' . implode(',:', $keys) . ')';
        $this  -> execute($sql, $data);
        $data['id'] = $this  -> get_last_id();

        $this -> _after_add($data);
        return $this  -> get_last_id();
    }

    protected function _before_save(&$data) {

    }

    protected function _after_save(&$data) {

    }

    function save($data = null) {
        if (empty($data)) {
            $data = $this -> data;
        }
        $this -> _before_save($data);
        $sql = 'update ' . $this -> table_name . ' set';
        foreach ($data as $key => $val) {
            if ($key !== 'id') {
                $sql .= '`' . $key . '`=' . ':' . $key . ',';
            }
        }
        $sql = substr($sql, 0, -1);
        if (isset($data['id'])) {
            $sql .= ' where id=' . $data['id'];
            $parm = $data;
            unset($parm['id']);
            $result = $this  -> execute($sql, $parm);
            $this -> _after_save($data);
            return $result;
        } elseif (!empty($this -> options['where'])) {
            $sql .= ' where 1=1 ';
            foreach ($this -> options['where'] as $val) {
                $sql .= $this -> _build_query_string($val);
            }
            if (isset($this -> options['parm'])) {
                $parm = array_merge($data, $this -> options['parm']);
            }
            $this -> options = array();
            $result = $this  -> execute($sql, $parm);
            $this -> _after_save($data);
            return $result;
        } else {
            return false;
        }
    }

    function set_field($key, $val) {
        $data[$key] = $val;
        return $this -> save($data);
    }

    function delete($id = null) {
        $sql = 'delete from ' . $this -> table_name;

        if (!empty($id)) {
            if (is_array($id)) {
                $map[] = array('id', 'in', $id);
            }
            if (is_string($id)) {
                $map[] = array('id', 'eq', $id);
            }
            $this -> where($map);
        }

        $where = ' where 1=1 ';
        foreach ($this -> options['where'] as $val) {
            $where .= $this -> _build_query_string($val);
        }

        $sql .= $where;
        //echo $sql;
        $parm = $this -> options['parm'];
        $this -> options = array();
        return $this  -> execute($sql, $parm);
    }

    function get_field($field, $is_array = false) {
        $arr_field = array_filter(explode(',', $field));
        $field_count = count($arr_field);
        list($first_field) = $arr_field;

        $this -> select($field);
        if ($field_count > 1) {
            $is_array = true;
        }
        if ($is_array) {
            $new = array();
            $list = $this -> get_list();
            if ($field_count == 1) {
                foreach ($list as $key => $val) {
                    $new[] = $val[$first_field];
                }
            }
            if ($field_count == 2) {
                list($first_field, $second_field) = $arr_field;
                foreach ($list as $key => $val) {
                    $new[$val[$first_field]] = $val[$second_field];
                }
            }
            if ($field_count > 2) {
                foreach ($list as $key => $val) {
                    $new[$val[$first_field]] = $val;
                }
            }
            return $new;
        } else {
            $vo = $this -> find();
            return $vo[$field];
        }
    }


    function get_last_sql() {
        echo $this -> last_sql;
        return $this -> last_sql;
    }

    function get_db_fields($table_name = null) {
        if (empty($table_name)) {
            $table_name = $this -> table_name;
        } else {
            $table_name = $this -> table_prefix . $table_name;
        }
        return parent::get_db_fields($table_name);
    }

    function get_select_fields() {
        if (isset($this -> options['select'])) {
            $fields = explode(',', $this -> options['select']);
        } else {
            $fields = array('*');
        }
        $return = array();
        foreach ($fields as $val) {
            $val = trim($val);
            if (strpos($val, '.*') !== false) {
                list($table, $field) = explode('.', $val);
                $return = array_merge($return, $this -> get_db_fields($table));
                continue;
            }
            if (strpos($val, '*') !== false) {
                $return = array_merge($return, $this -> get_db_fields());
                continue;
            }
            if (strpos($val, '.') !== false) {
                list($table, $alias) = explode(' ', $val);
                $return[] = $alias;
                continue;
            }
            $return[] = $val;
        }
        return $return;
    }

    private function filter($field, $val) {
        if (!isset($this -> filter_config)) {
            return $val;
        }
        if (isset($this -> filter_config['skip'])) {
            $skip_fields = array_filter(explode(',', $this -> filter_config['skip']));
            if (in_array($field, $skip_fields)) {
                return $val;
            }
        }

        $filter_function = $this -> filter_config;
        if (isset($filter_function['skip'])) {
            unset($filter_function['skip']);
        }

        foreach ($filter_function as $fn => $fields) {
            $fields = array_filter(explode(',', $fields));
            if (method_exists($this, $fn) && in_array($field, $fields)) {
                $val = $this -> $fn($val);
            }
        }

        return $val;
    }

    private function _build_query_string($data, $is_first = false) {
        $count = count($data);
        if ($count == 2) {
            list($operator, $val) = $data;
            $logic = 'and';
        }
        if ($count == 3) {
            list($field, $operator, $val) = $data;
            $logic = 'and';
        }
        if ($count == 4) {
            list($field, $operator, $val, $logic) = $data;
        }
        if (!isset($this -> options['parm'])) {
            $this -> options['parm'] = array();
        }
        if ($is_first) {
            $logic = '';
        }
        switch ($operator) {
            case 'eq' :
                $count = count($this -> options['parm']) + 1;
                $this -> options['parm']['parm_' . $count] = $val;
                $query = $logic . ' ' . $field . '=:parm_' . $count . ' ';
                break;

            case 'neq' :
                $count = count($this -> options['parm']) + 1;
                $this -> options['parm']['parm_' . $count] = $val;
                $query = $logic . ' ' . $field . '<>:parm_' . $count . ' ';

                break;

            case 'gt' :
                $count = count($this -> options['parm']) + 1;
                $this -> options['parm']['parm_' . $count] = $val;
                $query = $logic . ' ' . $field . '<:parm_' . $count . ' ';
                break;

            case 'egt' :
                $count = count($this -> options['parm']) + 1;
                $this -> options['parm']['parm_' . $count] = $val;
                $query = $logic . ' ' . $field . '>=:parm_' . $count . ' ';
                break;

            case 'lt' :
                $count = count($this -> options['parm']) + 1;
                $this -> options['parm']['parm_' . $count] = $val;
                $query = $logic . ' ' . $field . '<:parm_' . $count . ' ';
                break;

            case 'elt' :
                $count = count($this -> options['parm']) + 1;
                $this -> options['parm']['parm_' . $count] = $val;
                $query = $logic . ' ' . $field . '<=:parm_' . $count . ' ';
                break;

            case 'like' :
                $count = count($this -> options['parm']) + 1;
                $this -> options['parm']['parm_' . $count] = '%' . $val . '%';
                $query = $logic . ' ' . $field . ' like :parm_' . $count . ' ';

                break;

            case 'nolike' :
                $count = count($this -> options['parm']) + 1;
                $this -> options['parm']['parm_' . $count] = $val;
                $query = $logic . ' ' . $field . 'not like \'%:parm_' . $count . '%\' ';
                break;

            case 'in' :
                if (!is_array($val)) {
                    $val = explode(',', $val);
                }
                $query = $logic . ' ' . $field . ' in (';
                $count = count($this -> options['parm']);
                foreach ($val as $sub_val) {
                    $count++;
                    $this -> options['parm']['parm_' . $count] = $sub_val;
                    $query .= ':parm_' . $count . ',';
                }
                $query = substr($query, 0, -1);
                $query .= ')';
                break;

            case 'notin' :
                if (is_array($val)) {
                    $query = $logic . ' ' . $field . 'not in (';
                    $count = count($this -> options['parm']);
                    foreach ($val as $sub_val) {
                        $count++;
                        $this -> options['parm']['parm_' . $count] = $sub_val;
                        $query .= ':parm_' . $count . ',';
                    }
                    $query = substr($query, 0, -1);
                    $query .= ')';
                } else {
                    $query = $logic . ' 1=1';
                }
                break;

            case 'is' :
                $query = $logic . ' ' . $field . ' is ' . $val . ' ';
                break;

            case 'exists' :
                $query = $logic . ' ' . $field . ' exists ' . $val . ' ';
                break;

            case 'string' :
                $query = $logic . ' ' . $val . ' ';
                break;

            case 'complex' :
                $query = $logic . '(';
                foreach ($val as $sub_key => $sub_val) {
                    $query .= $this -> _build_query_string($sub_val, $sub_key == 0);
                }
                $query .= ') ';
                break;

            default :
                break;
        }
        if (empty($query)) {
            return '';
        }
        return $query;
    }

    private function _filter_request($val) {
        if ($val == '') {
            return false;
        } else {
            return true;
        }
    }


}