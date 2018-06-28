<?php
namespace razonyang\yii\log;

interface Rotate
{
    /**
     * Rotate logs.
     */
    public function rotate();

    /**
     * @return bool indicate whether the logs can be rotated.
     */
    public function canRotate();
}