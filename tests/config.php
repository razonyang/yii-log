<?php

return [
    'id' => 'app-test',
    'basePath' => __DIR__,
    'bootstrap' => ['log'],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=testdb',
            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8mb4',
            'tablePrefix' => 't_',
        ],

        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'logFile' => __DIR__ . '/test.log',
                ],
                'db' => [
                    'class' => \razonyang\yii\log\DbTarget::class,
                    'levels' => ['error', 'warning'],
                    'logTable' => '{{%log}}',

                    // rotate settings
                    'rotateInterval' => 100000,
                    // rotate mutex settings
                    'rotateMutex' => 'mutex',
                    'rotateMutexKey' => 'log_rotate',
                    'rotateMutexAcquireTimeout' => 0,
                ],
            ],
        ],

        // mutex is required by log rotate.
        'mutex' => [
            'class' => \yii\mutex\FileMutex::class,
        ],
    ],

    'controllerMap' => [
        'migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'migrationPath' => [
                '@vendor/razonyang/yii2-log/src/migrations',
            ],
        ],
        'log' => [
            'class' => \razonyang\yii\log\LogController::class,
        ]
    ],
];