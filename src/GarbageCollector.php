<?php
namespace razonyang\yii\log;

interface GarbageCollector
{
    /**
     * @return int the number of garbage that has been cleaned.
     */
    public function gc();
}