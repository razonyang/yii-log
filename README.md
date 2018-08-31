# Enhanced DB Target for Yii2 Log Component

[![Packagist](https://img.shields.io/packagist/dt/razonyang/yii-log.svg?style=flat-square)](https://packagist.org/packages/razonyang/yii-log)
[![Packagist](https://img.shields.io/packagist/v/razonyang/yii-log.svg?style=flat-square)](https://github.com/razonyang/yii-log/releases)
[![Travis](https://img.shields.io/travis/razonyang/yii-log.svg?style=flat-square)](https://travis-ci.org/razonyang/yii-log)

I wrote this extension for resolving the following problems:

1. The logs are chaotic, I cannot distinguish which logs are came from the same requests.
 It is hard to debug in concurrent scenarios.
2. The `yii\log\DbTarget` does not provide GC feature.

## Installation

```
composer require --prefer-dist razonyang/yii-log
```

## Usage

The usage is similar to `yii\log\DbTarget`.

### Configuration

```php
[

    'components' => [

        'log' => [
            'targets' => [
                [
                    'class' => \razonyang\yii\log\DbTarget::class,
                    'levels' => ['error', 'warning'],
                    'logTable' => '{{%log}}',
                    'logMessageTable' => '{{%log_message}}',

                    // garbage collection
                    'maxLifeTime' => 30*24*3600, // 30 days
                ],
            ],
        ],

    ],


    // migrate and rotate settings for console application.
    'controllerMap' => [
        'migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'migrationPath' => [

                '@vendor/razonyang/yii-log/src/migrations',

            ],
        ],
        'log' => [
            'class' => \razonyang\yii\log\LogController::class,
        ]
    ],

]
```

### Migrate

```
./yii migrate
```

### Garbage Collection

```
./yii log/gc
```

