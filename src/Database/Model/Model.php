<?php
/**
 * Created by PhpStorm.
 * User: haoyanfei
 * Date: 16/6/16
 * Time: 下午5:27
 */

namespace Kerisy\Database\Model;

use Kerisy\Database\Configuration;
use \Kerisy\Database\Connection;


abstract class Model
{
    static public $connection = null;

    public $configure;

    protected $table;

    public $debug;


    public function signton():Connection
    {
        if (is_null(self::$connection)) {
            $this->setDatabaseConfigure();

            $driver = $this->getDriver();
            $configure = new Configuration($this->debug);

            $configure->setParameters($this->configure);
            self::$connection = (new Connection($driver, $configure))->setTable($this->table);
        }
        return self::$connection;

    }

    abstract public function getDriver();

    abstract public function setDatabaseConfigure();

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->signton(), $method], $parameters);
    }

    static public function __callStatic($method, $parameters)
    {
        $static = new static;
        return call_user_func_array([$static, $method], $parameters);
    }


    public function setDebug($debug)
    {
        $this->debug = $debug;
    }


}