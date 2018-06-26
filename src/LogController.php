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
                Yii::info('the log target does not implement ' . Rotate::class . ' : ' . get_class($target));
                continue;
            }

            try {
                $target->rotate();
            } catch (\Exception $e) {
                Yii::error($e, __METHOD__);
            }
        }
    }
}