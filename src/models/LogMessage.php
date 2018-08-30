<?php
namespace razonyang\yii\log\models;

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
class LogMessage extends BaseActiveRecord
{
    public static $tableName = '{{%log_message}}';
}