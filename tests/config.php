<?php

return [
    'id' => 'app-test',
    'basePath' => __DIR__,
    'bootstrap' => ['log'],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=testdb',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'tablePrefix' => 't_',
        ],

        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => __DIR__ . '/test.log',
                ],
                'db' => [
                    'class' => 'razonyang\yii\log\DbTarget',
                    'levels' => ['error', 'warning'],
                    'logTable' => '{{%log}}',
                    'logMessageTable' => '{{%log_message}}',
                ],
            ],
        ],
    ],

    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@vendor/razonyang/yii-log/src/migrations',
            ],
        ],
        'log' => [
            'class' => 'razonyang\yii\log\LogController',
        ]
    ],
];