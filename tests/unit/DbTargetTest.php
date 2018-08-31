<?php

class DbTargetTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var \razonyang\yii\log\DbTarget
     */
    protected $target;

    protected function _before()
    {
        $this->target = Yii::$app->log->targets['db'];
    }

    protected function _after()
    {

    }

    public function testExport()
    {
        // generate logs.
        $messages = [];
        $times = rand(1, 10);
        for ($i = 0; $i < $times; $i++) {
            $messages[] = [
                $i,
                $i % 2 == 0 ? \yii\log\Logger::LEVEL_ERROR : \yii\log\Logger::LEVEL_WARNING,
                $i,
                microtime(true),
            ];
        }

        $this->target->messages = $messages;
        // export logs.
        $this->target->export();

        $logId = $this->target->getLogId();

        // check log.
        $app = Yii::$app;
        $response = $app->getResponse();
        $logConditions = [
            'id' => $logId,
            'application' => $app->id,
            'requested_at' => $this->target->requestedAt,
            'exit_status' => $response->exitStatus,
        ];
        $this->tester->seeRecord('razonyang\yii\log\models\Log', $logConditions);

        // check messages
        foreach ($messages as $message) {
            list($msg, $level, $category, $timestamp) = $message;
            $this->tester->seeRecord('razonyang\yii\log\models\LogMessage', [
                'log_id' => $logId,
                'level' => $level,
                'message' => $msg,
                'category' => $category,
                'message_time' => $timestamp,
            ]);
        }
    }

    public function testGc()
    {
        // insert some logs
        $messages = [];
        $times = rand(1, 10);
        for ($i = 0; $i < $times; $i++) {
            $messages[] = [
                $i,
                $i % 2 == 0 ? \yii\log\Logger::LEVEL_ERROR : \yii\log\Logger::LEVEL_WARNING,
                $i,
                microtime(true),
            ];
        }

        $this->target->messages = $messages;
        // export logs.
        $this->target->export();

        // changed log max life time
        $this->target->maxLifeTime = 24 * 3600;
        $this->assertEquals([0, 0], $this->target->gc());

        // disabled gc
        $this->target->maxLifeTime = 0;
        $this->assertFalse($this->target->gc());

        // changed log max life time as one second and sleep.
        $maxLifeTime = 1;
        $this->target->maxLifeTime = $maxLifeTime;
        // make sure the messages has been expired.
        sleep($maxLifeTime + 1);

        $this->assertEquals([1, count($messages)], $this->target->gc());
    }
}