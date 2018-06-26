# Enhanced DB Target for Yii2 Log Component

I wrote this extension for resolving the following problems:

1. The logs are chaotic, I cannot distinguish which logs are came from the same requests.
 It is hard to debug in concurrent scenarios.
2. The `yii\log\DbTarget` does not provide rotate feature.

## Installation

```
composer require --prefer-dist razonyang/yii2-log
```

## Usage

The usage is similar to `yii\log\DbTarget`.

### Configuration

```php
[
    ...

    'components' => [
        ...
        'log' => [
            'targets' => [
                [
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
        ...
    ],

    ...

    // migrate and rotate settings for console application.
    'controllerMap' => [
        'migrate' => [
            'class' => yii\console\controllers\MigrateController::class,
            'migrationPath' => [
                ...
                '@vendor/razonyang/yii2-log/src/migrations',
                ...
            ],
        ],
        'log' => [
            'class' => razonyang\yii\log\LogController::class,
        ]
    ],

    ...
]
```

### Migrate

```
./yii migrate
```

### Rotate

```
./yii log/ratote
```

