<?php
namespace razonyang\yii\log;

interface GarbageCollector
{
    /**
     * @return bool|array the number of logs and messages that has been cleaned.
     * If gc has been disabled, return false instead.
     */
    public function gc();
}