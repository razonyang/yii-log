<?php
namespace razonyang\yii\log;

use yii\db\ActiveRecord;

/**
 * Class Log
 *
 * @property string $request_id
 * @property integer $log_id
 * @property double $requested_at
 * @property integer $level
 * @property string $category
 * @property string $prefix
 * @property string $message
 * @property double $log_time
 */
class Log extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%log}}';
    }
}