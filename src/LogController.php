<?php
namespace razonyang\yii\log;

use Yii;
use yii\console\Controller;

class LogController extends Controller
{
    public function actionRotate()
    {
        $log = Yii::$app->getLog();
        foreach ($log->targets as $target) {
            if (!$target instanceof Rotate) {
                $msg = 'the log target does not implement razonyang\yii\log\Rotate: ' . get_class($target);
                Yii::info($msg, __METHOD__);
                echo $msg . PHP_EOL;
                continue;
            } else if (!$target->canRotate()) {
                $msg = 'can not rotate or rotate has been disabled: ' . get_class($target);
                Yii::info($msg, __METHOD__);
                echo $msg . PHP_EOL;
                continue;
            }

            try {
                $target->rotate();
                echo 'rotated successfully' . PHP_EOL;
            } catch (\Exception $e) {
                Yii::error($e, __METHOD__);
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }

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
                $target->gc();
            } catch (\Exception $e) {
                Yii::error($e, __METHOD__);
            }
        }
    }
}