<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 2018/1/10
 * Time: 12:05
 */

namespace framework;


class Db
{
    /**
     * @var \PDO
     */
    protected $connect;

    protected $name;
    protected $table_prefix;
    protected $table_name;

    function __construct($name = null)
    {
        $this->name = $name;
        $this->table_prefix = Config::get('db_prefix');
        $this->table_name = $this->table_prefix . $this->name;
        if (!$this->connect) {
            // 连接数据库
            $this->connect(Config::get('db_host'), Config::get('db_user'), Config::get('db_password'), Config::get('db_name'));
        }

    }

    // 连接数据库
    public function connect($host, $user, $pass, $dbname)
    {
        try {
            $dsn = sprintf("mysql:host=%s;dbname=%s;charset=utf8", $host, $dbname);
            $conn = new \PDO($dsn, $user, $pass);
            // 设置为异常模式
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connect = $conn;
        } catch (\PDOException $e) {
            exit('错误: ' . $e->getMessage());
        }
    }


    public function execute($sql, $parameters = null)
    {
        $conn = $this->connect;
        $statement = $conn->prepare($sql);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        $statement->execute($parameters);
        $affected_rows = $statement->rowcount();
        $statement = null;
        $conn = null;
        return $affected_rows;
    }

    public function query($sql, $parameters = null)
    {
        $conn = $this->connect;
        $statement = $conn->prepare($sql);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        $statement->execute($parameters);
        $rs = $statement->fetchAll();
        $statement = null;
        $conn = null;
        return $rs;
    }

    public function get_db_fields($table_name)
    {
        $fields = array();
        $sql = 'show columns from ' . $table_name;
        $statement = $this->connect->prepare($sql);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        $statement->setFetchMode(\PDO::CASE_LOWER);
        $statement->execute();

        $rs = $statement->fetchAll();
        $statement = null;
        $conn = null;
        $rs = $this->rotate($rs, 'Field');
        return $rs;
    }

    protected function rotate($a, $field = null) {
        $b = array();
        if (is_array($a)) {
            foreach ($a as $val) {
                foreach ($val as $k => $v) {
                    $b[$k][] = $v;
                }
            }
        }
        if (!empty($b) && !empty($field)) {
            return $b[$field];
        }
        return $b;
    }

    public function get_last_id()
    {
        return $this->connect->lastInsertId();
    }

}