<?php
namespace razonyang\yii\log\models;

use Ramsey\Uuid\Uuid;
use yii;
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;
use yii\web\Request as WebRequest;
use yii\web\Response as WebResponse;


/**
 * Class Log
 *
 * @property string $id request id
 * @property string $application application name
 * @property string $route requested route
 * @property integer $exit_status exit status
 * @property string $url request url
 * @property string $method request method
 * @property string $ip IP address
 * @property string $user_agent user agent
 * @property string $raw_body raw body
 * @property integer $status HTTP status code
 * @property string $status_text HTTP status text
 * @property string $context context
 * @property double $requested_at request time
 */
class Log extends yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%log}}';
    }

    public function rules()
    {
        return [
            [['id'], 'required'],
            [['application', 'route', 'url', 'method', 'status_text', 'ip', 'user_agent', 'raw_body', 'context'], 'default', 'value' => ''],
            [['exited_status', 'status'], 'default', 'value' => 0],
        ];
    }
}