<?php
namespace razonyang\yii\log\models;

use yii;
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;
use yii\web\Request as WebRequest;
use yii\web\Response as WebResponse;
use yii\web\Session as WebSession;
use yii\web\User as WebUser;

/**
 * Class Log
 *
 * @property string $id request id
 * @property string $application application name
 * @property string $route requested route
 * @property integer $exit_status exit status
 * @property string $url request url
 * @property string $method request method
 * @property integer $status HTTP status code
 * @property string $status_text HTTP status text
 * @property string $ip IP address
 * @property string $user_id user ID
 * @property string $session_id session ID
 * @property string $raw_body
 * @property double $requested_at request time
 */
class Log extends BaseActiveRecord
{
    public static $tableName = '{{%log}}';

    public function rules()
    {
        return [
            [[''], 'default'],
        ];
    }

    /**
     * @param WebApplication|ConsoleApplication $app
     * @return static
     */
    public function generate($app)
    {
        $request = $app->getRequest();
        $response = $app->getResponse();

        $data = [
            'id' => '',
            'application' => $app->name,
            'route' => $app->requestedRoute,
            'exit_status' => $response->exitStatus,
            'requested_at' => $_SERVER['REQUEST_TIME_FLOAT'],
        ];

        if ($request instanceof WebRequest) {
            $data['ip'] = $request->getUserIP();
            $data['method'] = $request->getMethod();
            $data['raw_body'] = $request->rawBody;

            /* @var $user WebUser */
            $user = Yii::$app->has('user', true) ? Yii::$app->get('user') : null;
            if ($user && ($identity = $user->getIdentity(false))) {
                $data['user_id'] = $identity->getId();
            }

            /* @var $session WebSession */
            $session = Yii::$app->has('session', true) ? Yii::$app->get('session') : null;
            if ($session && $session->getIsActive()) {
                $data['session_id'] = $session->getId();
            }
        }

        if ($response instanceof WebResponse) {
            $data['status'] = $response->statusCode;
            $data['status_text'] = $response->statusText;
        }

        return new static($data);
    }
}