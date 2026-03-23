<?php

namespace Yourcormorant\LaravelHubs\Abstracts;

interface Positionable
{
    /**
     * Задать классу пайпа позицию и получить ссылку на него
     *
     * @param float $position
     * @return string
     */
    public static function withPosition(float $position): string;

    /**
     * Получить позицию пайпа
     *
     * @return float
     */
    public static function getPosition(): float;
}
