<?php
namespace razonyang\yii\log;

use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\TableSchema;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\log\LogRuntimeException;
use yii\mutex\Mutex;

/**
 * Class DbTarget An Enhanced DB Target for Yii2 Log Component.
 *
 * @property integer $rotateInterval
 */
class DbTarget extends \yii\log\DbTarget implements Rotate
{
    /**
     * @inheritdoc
     */
    public function export()
    {
        if ($this->db->getTransaction()) {
            // create new database connection, if there is an open transaction
            // to ensure insert statement is not affected by a rollback
            $this->db = clone $this->db;
        }

        $tableName = $this->db->quoteTableName($this->logTable);
        $sql = "INSERT INTO $tableName ([[log_id]], [[request_id]], [[requested_at]], [[level]], [[category]], [[log_time]], [[prefix]], [[message]])
                VALUES (:log_id, :request_id, :requested_at, :level, :category, :log_time, :prefix, :message)";
        $command = $this->db->createCommand($sql);

        $requestedAt = $this->getRequestTime();
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;
            if (!is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    $text = (string)$text;
                } else {
                    $text = VarDumper::export($text);
                }
            }
            if ($command->bindValues([
                    ':log_id' => $this->logId++,
                    ':request_id' => $this->getRequestId(),
                    ':requested_at' => $requestedAt,
                    ':level' => $level,
                    ':category' => $category,
                    ':log_time' => $timestamp,
                    ':prefix' => $this->getMessagePrefix($message),
                    ':message' => $text,
                ])->execute() > 0) {
                continue;
            }
            throw new LogRuntimeException('Unable to export log through database!');
        }
    }

    /**
     * The unique log ID for each request.
     * @var int log auto increment ID.
     */
    private $logId = 1;

    /**
     * @var integer The request time.
     */
    private $requestTime;

    /**
     * @return integer The request time.
     */
    public function getRequestTime()
    {
        if ($this->requestTime === null) {
            $this->requestTime = $_SERVER['REQUEST_TIME_FLOAT'];
        }

        return $this->requestTime;
    }

    /**
     * @var int The length of microseconds of request time.
     */
    public $microsecondsLength = 7;

    /**
     * @var string The request ID.
     */
    private $requestId;

    /**
     * @var integer The length of request ID.
     */
    public $requestIdLength = 32;

    /**
     * @return string The request ID.
     * Generates one if it does not exists.
     */
    public function getRequestId()
    {
        if ($this->requestId === null) {
            $this->requestId = $this->generateRequestId();
        }

        return $this->requestId;
    }

    /**
     * Generates request ID.
     * @return string request ID.
     */
    public function generateRequestId()
    {
        // format request time
        $requestTime = $this->getRequestTime();
        $time = explode('.', $requestTime);
        $seconds = $time[0];
        $microseconds = isset($time[1]) ? $time[1] : 0;
        $microseconds = substr(
            str_pad($microseconds, $this->microsecondsLength, '0'),
            0,
            $this->microsecondsLength
        );
        $date = date('YmdHis.', $seconds) . $microseconds . '.';

        // generates random string.
        $randomLength = $this->requestIdLength - strlen($date);
        if ($randomLength < 0) {
            throw new InvalidArgumentException('the requestIdLength is too small');
        } else if ($randomLength < 2) {
            Yii::warning(
                'the requestIdLength is too small, it is recommend to increase it to avoid generating the same request ID and losing logs in concurrent scenarios',
                __METHOD__
            );
        }
        $random = $randomLength > 0 ? Yii::$app->getSecurity()->generateRandomString($randomLength) : '';
        return $date . $random;
    }

    /**
     * @var int rotate interval, if amount of logs is large than interval, rotate will be started.
     */
    private $rotateInterval = 100000;

    /**
     * @return int rotate interval
     */
    public function getRotateInterval()
    {
        return $this->rotateInterval;
    }

    /**
     * Set rotate interval.
     * @param int $interval
     */
    public function setRotateInterval($interval)
    {
        $this->rotateInterval = intval($interval);
    }

    /**
     * The Mutex for lock rotate process.
     * @var mixed
     */
    public $rotateMutex = 'mutex';

    /**
     * @var string
     * @see Mutex::acquireLock()
     */
    public $rotateMutexKey = 'log_rotate';

    /**
     * @var int
     * @see Mutex::acquireLock()
     */
    public $rotateMutexAcquireTimeout = 0;

    /**
     * Return rotate table name.
     * @return string rotate table name.
     */
    public function rotateTableName()
    {
        return $this->getLogTableMeta()->fullName . '_' . date('Ymd');
    }

    /**
     * @inheritdoc
     */
    public function rotate()
    {
        try {
            /** @var null|Mutex $mutex */
            $mutex = Yii::$app->get($this->rotateMutex);
            if (!$mutex) {
                throw new InvalidConfigException('rotate mutex class is required');
            }
            if (!$mutex instanceof Mutex) {
                throw new InvalidConfigException('rotate mutex class does not implement ' . Mutex::className());
            }
            if (!$mutex->acquire($this->rotateMutexKey, $this->rotateMutexAcquireTimeout)) {
                throw new \RuntimeException('could not acquire rotate mutex: ' . $this->rotateMutexKey);
            }
            $logTableMeta = $this->getLogTableMeta();
            $logTableName = $logTableMeta->fullName;

            $db = clone $this->db;
            $amount = ActiveRecord::find()
                ->from($logTableName)
                ->count('*', $db);
            Yii::info($amount);
            Yii::info($this->rotateInterval);
            if ($amount < $this->rotateInterval) {
                throw new InvalidCallException('the rotate interval has not been reached yet');
            }

            $transaction = $db->beginTransaction();
            try {
                // generate rotate table.
                $rotateTableName = $this->rotateTableName();
                if (!$db->getTableSchema($rotateTableName, true)) {
                    // create table if it does not exists.
                    $createSqls[] = $db->getQueryBuilder()->createTable(
                        $rotateTableName,
                        ArrayHelper::map($logTableMeta->columns, 'name', 'dbType')
                    );
                    $createSqls[] = $db->getQueryBuilder()->addPrimaryKey('PRIMARY KEY', $rotateTableName, ['request_id', 'log_id'], true);
                    $createSqls[] = $db->getQueryBuilder()->createIndex('idx_request_id', $rotateTableName, 'request_id');
                    $createSqls[] = $db->getQueryBuilder()->createIndex('idx_requested_at', $rotateTableName, 'requested_at');
                    $createSqls[] = $db->getQueryBuilder()->createIndex('idx_log_level', $rotateTableName, 'level');
                    $createSqls[] = $db->getQueryBuilder()->createIndex('idx_log_category', $rotateTableName, 'category');

                    foreach ($createSqls as $createSql) {
                        $db->createCommand($createSql)->execute();
                    }
                    Yii::info('created rotate log table: ' . $rotateTableName, __METHOD__);
                }

                // update log rotate field for rotating and removing rotated logs.
                $updateSql = <<<EOL
UPDATE {$logTableName}
SET rotate = 1
EOL;
                $db->createCommand($updateSql)->execute();

                // import logs to rotate table.
                $columns = implode(', ', $logTableMeta->columnNames);
                $dataSql = <<<EOL
INSERT INTO {$rotateTableName}($columns)
SELECT {$columns} FROM {$logTableName}
WHERE rotate = 1
EOL;
                $rotateRows = $db->createCommand($dataSql)->execute();
                Yii::info('rotated logs: ' . $rotateRows, __METHOD__);

                // remove logs from original table which has been rotated.
                $rmSql = <<<EOL
DELETE FROM {$logTableName}
WHERE rotate = 1
EOL;
                $rmRows = $db->createCommand($rmSql)->execute();
                Yii::info('removed rotated logs: ' . $rmRows, __METHOD__);

                $transaction->commit();
                // release mutex.
                $mutex->release($this->rotateMutexKey);
            } catch (\Exception $e) {
                $transaction->rollBack();
                // release mutex.
                $mutex->release($this->rotateMutexKey);
                // rethrow it.
                throw $e;
            }
        } catch (\Exception $e) {
            // release mutex.
            $mutex->release($this->rotateMutexKey);
            // rethrow it.
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function canRotate()
    {
        return $this->rotateInterval > 0;
    }

    /**
     * @var TableSchema the table schema of log table.
     */
    private $logTableMeta;

    /**
     * @return TableSchema
     */
    private function getLogTableMeta()
    {
        if ($this->logTableMeta === null) {
            $this->logTableMeta = $this->db->getTableSchema($this->logTable, true);
        }

        return $this->logTableMeta;
    }
}