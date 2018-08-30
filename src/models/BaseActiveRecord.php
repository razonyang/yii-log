<?php
namespace razonyang\yii\log\models;

use yii\db\ActiveRecord;

class BaseActiveRecord extends ActiveRecord
{
    public static $tableName;

    public static function tableName()
    {
        return self::$tableName;
    }
}