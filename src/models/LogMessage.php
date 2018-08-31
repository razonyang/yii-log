<?php
namespace razonyang\yii\log\models;

use yii\db\ActiveRecord;

/**
 * Class Log Message
 *
 * @property string $log_id
 * @property double $requested_at
 * @property integer $message_id
 * @property integer $level
 * @property string $category
 * @property string $prefix
 * @property string $message
 * @property double $message_time
 */
class LogMessage extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%log_message}}';
    }

    public function rules()
    {
        return [
            [['log_id', 'message_id', 'category', 'message', 'message_time'], 'required'],
            [['level'], 'integer'],
            [['category', 'prefix', 'message'], 'string'],
        ];
    }
}