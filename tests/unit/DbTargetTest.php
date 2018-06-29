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

    /**
     * @var string log table name
     */
    protected $tableName = 't_log';

    protected function _before()
    {
        $this->target = new \razonyang\yii\log\DbTarget();
    }

    protected function _after()
    {
    }

    public function testExport()
    {
        // generate logs.
        $logs = [];
        $times = rand(1, 10);
        for ($i = 0; $i < $times; $i++) {
            $logs[] = [
                'err' . $i,
                \yii\log\Logger::LEVEL_ERROR,
                $i,
                microtime(true),
            ];
        }

        // export logs.
        $target = Yii::$app->log->targets['db'];
        $target->messages = $logs;
        $target->export();

        $requestId = $target->getRequestId();

        // verify logs.
        foreach ($logs as $log) {
            list($message, $level, $category, $timestamp) = $log;
            $this->tester->seeRecord('razonyang\yii\log\Log', [
                'request_id' => $requestId,
                'level' => $level,
                'message' => $message,
                'category' => $category,
            ]);
        }
    }

    public function testCanRotate()
    {
        $rotateIntervals = [
            0 => false,
        ];
        $ranges = [
            [-10000000, 1, false],
            [1, 10000000, true],
        ];
        $times = rand(1, 10);
        // generate random intervals.
        foreach ($ranges as $range) {
            for ($i = 0; $i < $times; $i++) {
                $interval = rand($range[0], $range[1]);
                $rotateIntervals[$interval] = $range[2];
            }
        }

        foreach ($rotateIntervals as $rotateInterval => $expected) {
            $this->target->rotateInterval = $rotateInterval;
            $this->assertEquals($expected, $this->target->canRotate());
        }
    }

    public function testRotate()
    {
        // generate logs
        $logs = [];
        $times = 100;
        for ($i = 0; $i < $times; $i++) {
            $logs[] = [
                'err' . $i,
                \yii\log\Logger::LEVEL_ERROR,
                $i,
                microtime(true),
            ];
        }

        // export logs.
        $this->target->messages = $logs;
        codecept_debug('export messages');
        $this->target->export();

        $this->target->rotateInterval = $times - rand(0, $times - 1);
        $this->target->rotate();

        $rotateTableName = $this->target->rotateTableName();
        $requestId = $this->target->getRequestId();

        $rotateModel = <<<EOL
class {$rotateTableName} extends \yii\db\ActiveRecord {
    public static function tableName() {
        return '{$rotateTableName}';
    }
}
EOL;
        eval($rotateModel);

        foreach ($logs as $log) {
            list($message, $level, $category, $timestamp) = $log;
            $this->tester->seeRecord($rotateTableName, [
                'request_id' => $requestId,
                'level' => $level,
                'message' => $message,
                'category' => $category,
            ]);
        }
    }
}