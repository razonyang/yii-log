<?php
namespace razonyang\yii\log;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

class LogController extends Controller
{
    /**
     * Garbage collection.
     */
    public function actionGc()
    {
        $log = Yii::$app->getLog();
        foreach ($log->targets as $target) {
            if (!$target instanceof GarbageCollector) {
                continue;
            }

            try {
                $response = $target->gc();
                if ($response === false) {
                    $this->stdout('GC has been disabled', Console::FG_RED);
                    continue;
                }
                list($logs, $messages) = $response;
                $this->stdout("Deleted logs: {$logs}, messages: {$messages}", Console::FG_GREEN);
            } catch (\Exception $e) {
                Yii::error($e, __METHOD__);
            }
        }
    }
}