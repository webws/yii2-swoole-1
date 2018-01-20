<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/12/14
 * Time: 下午4:09
 */

namespace tsingsun\swoole\db;


class Connection extends \yii\db\Connection
{

    protected $errorCount = 0;
    public $maxErrorTimes = 2;
    /**
     * @var array pool config
     */
    public $poolConfig;

    public $commandClass = 'tsingsun\swoole\db\Command';

    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function beginTransaction($isolationLevel = null)
    {
        try {
            return parent::beginTransaction($isolationLevel);
        } catch (\Throwable $exception) {
            if ($this->isConnectionError($exception) && $this->errorCount < $this->maxErrorTimes) {
                $this->close();
                $this->open();
                $this->errorCount++;
                return $this->beginTransaction($isolationLevel);
            }
            $this->errorCount = 0;
            throw  $exception;
        }
    }

    /**
     * 检查指定的异常是否为可以重连的错误类型
     *
     * @param \Exception $exception
     * @return bool
     */
    public function isConnectionError($exception)
    {
        if ($exception instanceof \PDOException) {
            $errorCode = $exception->getCode();
            if ($errorCode == 70100 || $errorCode == 2006 || $errorCode == 2013) {
                return true;
            }
        }
        $message = $exception->getMessage();
        if (strpos($message, 'Error while sending QUERY packet.') !== false) {
            return true;
        }
        // Error reading result set's header
        if (strpos($message, 'Error reading result set\'s header') !== false) {
            return true;
        }
        // MySQL server has gone away
        if (strpos($message, 'MySQL server has gone away') !== false) {
            return true;
        }
        return false;
    }

    protected function createPdoInstance()
    {
        $pdoClass = $this->pdoClass;
        if ($pdoClass === null) {
            $pdoClass = 'PDO';
            $driver = $this->getDriverName();
            if (isset($driver)) {
                if($driver === 'mysql'){
                    $pdoClass = 'tsingsun\swoole\db\mysql\PDO';
//                    $this->commandClass = 'tsingsun\swoole\db\mysql\Command';
                } elseif ($driver === 'mssql' || $driver === 'dblib') {
                    $pdoClass = 'yii\db\mssql\PDO';
                } elseif ($driver === 'sqlsrv') {
                    $pdoClass = 'yii\db\mssql\SqlsrvPDO';
                }
            }
        }

        $dsn = $this->dsn;
        if (strncmp('sqlite:@', $dsn, 8) === 0) {
            $dsn = 'sqlite:' . \Yii::getAlias(substr($dsn, 7));
        }
        if($this->poolConfig){
            \Yii::$app->getConnectionManager()->poolConfig[md5($dsn)] = $this->poolConfig;
        }
        return new $pdoClass($dsn, $this->username, $this->password, $this->attributes);
    }


}