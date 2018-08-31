<?php
namespace razonyang\yii\log;

use Ramsey\Uuid\Uuid;
use Yii;
use yii\helpers\VarDumper;
use yii\log\LogRuntimeException;
use yii\web\Request as WebRequest;
use yii\web\Response as WebResponse;

/**
 * Class DbTarget An Enhanced DB Target for Yii2 Log Component.
 *
 * @property double $requestedAt
 */
class DbTarget extends \yii\log\DbTarget implements GarbageCollector
{
    /**
     * @var string
     */
    public $logMessageTable = '{{%log_message}}';

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

        $logId = $this->getLogId();
        $requestedAt = $this->getRequestedAt();

        $tableName = $this->db->quoteTableName($this->logMessageTable);
        $sql = "INSERT INTO $tableName ( [[log_id]], [[requested_at]], [[message_id]], [[level]], [[category]], [[message_time]], [[prefix]], [[message]])
                VALUES (:log_id, :requested_at, :message_id, :level, :category, :message_time, :prefix, :message)";
        $command = $this->db->createCommand($sql);

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
                    ':log_id' => $logId,
                    ':requested_at' => $requestedAt,
                    ':message_id' => $this->messageId++,
                    ':level' => $level,
                    ':category' => $category,
                    ':message_time' => $timestamp,
                    ':prefix' => $this->getMessagePrefix($message),
                    ':message' => $text,
                ])->execute() > 0) {
                continue;
            }
            throw new LogRuntimeException('Unable to export log through database!');
        }
    }

    /**
     * The unique message ID for each log.
     * @var int
     */
    private $messageId = 1;

    /**
     * @var string log ID
     */
    private $logId;

    /**
     * @var float requested time
     */
    private $requestedAt;

    public function getRequestedAt()
    {
        if ($this->requestedAt === null) {
            $this->requestedAt = $_SERVER['REQUEST_TIME_FLOAT'];
        }
        return $this->requestedAt;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLogId()
    {
        if ($this->logId === null) {
            $logId = Uuid::uuid4()->toString();
            $requestedAt = $this->getRequestedAt();

            $table = $this->db->quoteTableName($this->logTable);
            $sql = "INSERT INTO $table ( [[id]], [[requested_at]], [[application]], [[route]], [[exit_status]], [[ip]], [[url]], [[method]], [[raw_body]], [[user_agent]], [[status]], [[status_text]])
                VALUES (:id, :requested_at, :application, :route, :exit_status, :ip, :url, :method, :raw_body, :user_agent, :status, :status_text)";
            $command = $this->db->createCommand($sql);
            $logData = [
                ':id' => $logId,
                ':requested_at' => $requestedAt,
                ':application' => '',
                ':route' => '',
                ':exit_status' => 0,
                ':ip' => '',
                ':url' => '',
                ':method' => '',
                ':raw_body' => '',
                ':user_agent' => '',
                ':status' => 0,
                ':status_text' => '',
            ];
            $app = Yii::$app;
            if ($app !== null) {
                $request = $app->getRequest();
                $response = $app->getResponse();

                $logData[':application'] = $app->name ?: '';
                $logData[':route'] = $app->requestedRoute ?: '';
                $logData[':exit_status'] = $response->exitStatus;

                if ($request instanceof WebRequest) {
                    $logData[':ip'] = $request->userIP ?: '';

                    try {
                        $logData[':url'] = $request->getAbsoluteUrl() ?: '';
                    } catch (\Exception $e) {

                    }
                    $logData[':method'] = $request->method ?: '';
                    $logData[':raw_body'] = $request->rawBody ?: '';
                    $logData[':user_agent'] = $request->userAgent ? substr($request->userAgent, 0, 255) : '';
                }

                if ($response instanceof WebResponse) {
                    $logData[':status'] = $response->statusCode ?: 0;
                    $logData[':status_text'] = $response->statusText ?: '';
                }
            }
            if ($command->bindValues($logData)->execute() == 0) {
                throw new LogRuntimeException('could not create log record');
            }

            $this->logId = $logId;
            $this->requestedAt = $requestedAt;
        }

        return $this->logId;
    }

    /**
     * @var int log max life time, default 30 days. Negative number to disable it.
     */
    public $maxLifeTime = 2592000;

    /**
     * @inheritdoc
     */
    public function gc()
    {
        if ($this->maxLifeTime <= 0) {
            return false;
        }

        $expiredTime = time() - $this->maxLifeTime;
        Yii::info('deleting logs those requested before' . date('Y-m-d H:i:s'), __METHOD__);

        $logMessageTable = $this->db->quoteTableName($this->logMessageTable);
        $delMessageSql = <<<SQL
DELETE FROM {$logMessageTable} WHERE requested_at < {$expiredTime}
SQL;
        $delMessages = $this->db->createCommand($delMessageSql)->execute();
        Yii::info('deleted log messages: ' . $delMessages, __METHOD__);

        $logTable = $this->db->quoteTableName($this->logTable);
        $delLogSql = <<<SQL
DELETE FROM {$logTable} WHERE requested_at < {$expiredTime}
SQL;
        $delLogs = $this->db->createCommand($delLogSql)->execute();
        Yii::info('deleted logs: ' . $delLogs, __METHOD__);

        return [$delLogs, $delMessages];
    }
}