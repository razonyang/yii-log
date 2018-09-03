<?php

use yii\base\InvalidConfigException;
use yii\db\Migration;
use razonyang\yii\log\DbTarget;

/**
 * Initializes log table.
 *
 * The indexes declared are not required. They are mainly used to improve the performance
 * of some queries about message levels and categories. Depending on your actual needs, you may
 * want to create additional indexes (e.g. index on `log_time`).
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 2.0.1
 */
class php extends Migration
{
    /**
     * @var DbTarget[] Targets to create log table for
     */
    private $dbTargets = [];

    /**
     * @throws InvalidConfigException
     * @return DbTarget[]
     */
    protected function getDbTargets()
    {
        if ($this->dbTargets === []) {
            $log = Yii::$app->getLog();

            $usedTargets = [];
            foreach ($log->targets as $target) {
                if ($target instanceof DbTarget) {
                    $currentTarget = [
                        $target->db,
                        $target->logTable,
                    ];
                    if (!in_array($currentTarget, $usedTargets, true)) {
                        // do not create same table twice
                        $usedTargets[] = $currentTarget;
                        $this->dbTargets[] = $target;
                    }
                }
            }

            if ($this->dbTargets === []) {
                throw new InvalidConfigException('You should configure "log" component to use one or more database targets before executing this migration.');
            }
        }

        return $this->dbTargets;
    }

    public function up()
    {
        foreach ($this->getDbTargets() as $target) {
            $this->db = $target->db;

            $tableOptions = null;
            if ($this->db->driverName === 'mysql') {
                // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
                $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
            }

            // create log table
            $this->createTable($target->logTable, [
                'id' => $this->char(36)->notNull(),
                'requested_at' => $this->double()
                    ->notNull()
                    ->comment('request time'),
                'application' => $this->char(64)
                    ->notNull()
                    ->defaultValue('')
                    ->comment('application ID'),
                'route' => $this->char(255)
                    ->notNull()
                    ->defaultValue('')
                    ->comment('requested route'),
                'exit_status' => $this->integer()
                    ->notNull()
                    ->defaultValue(0)
                    ->comment('exit status'),
                'url' => $this->text()
                    ->notNull()
                    ->defaultValue('')
                    ->comment('request url'),
                'method' => $this->char(7)
                    ->notNull()
                    ->defaultValue('')
                    ->comment('request method'),
                'ip' => $this->char(45)
                    ->notNull()
                    ->defaultValue('')
                    ->comment('IP address'),
                'user_agent' => $this->text()
                    ->notNull()
                    ->defaultValue('')
                    ->comment('user agent'),
                'raw_body' => $this->text()
                    ->notNull()
                    ->defaultValue('')
                    ->comment('raw body'),
                'status' => $this->integer()
                    ->notNull()
                    ->defaultValue(0)
                    ->comment('HTTP response status'),
                'status_text' => $this->char(128)
                    ->notNull()
                    ->defaultValue('')
                    ->comment('HTTP response status text'),
                'context' => $this->text()
                    ->notNull()
                    ->comment('context'),
                'extra' => $this->text()
                    ->notNull()
                    ->comment('extra info'),
            ], $tableOptions);

            $this->addPrimaryKey('PRIMARY KEY', $target->logTable, ['id']);
            $this->createIndex('idx_requested_at', $target->logTable, 'requested_at');
            $this->createIndex('idx_application', $target->logTable, 'application');
            $this->createIndex('idx_route', $target->logTable, 'route');
            $this->createIndex('idx_method', $target->logTable, 'method');

            // create log message table
            $this->createTable($target->logMessageTable, [
                'log_id' => $this->char(36),
                'message_id' => $this->integer()->comment('message id'),
                'requested_at' => $this->double()->comment('request time'),
                'level' => $this->tinyInteger(),
                'category' => $this->string(),
                'message_time' => $this->double(),
                'prefix' => $this->text(),
                'message' => $this->text(),
            ], $tableOptions);

            $this->addPrimaryKey('PRIMARY KEY', $target->logMessageTable, ['log_id', 'message_id']);
            $this->createIndex('idx_level', $target->logMessageTable, 'level');
            $this->createIndex('idx_category', $target->logMessageTable, 'category');
            $this->createIndex('idx_message_time', $target->logMessageTable, 'message_time');
        }
    }

    public function down()
    {
        foreach ($this->getDbTargets() as $target) {
            $this->db = $target->db;

            $this->dropTable($target->logTable);
            $this->dropTable($target->logMessageTable);
        }
    }
}
