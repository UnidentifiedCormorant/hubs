<?php

namespace Yourcormorant\LaravelHubs\Traits;

trait WithPositioning
{
    protected static float $position = 0;

    public static function withPosition(float $position): string
    {
        static::$position = $position;
        return static::class;
    }

    public static function getPosition(): float
    {
        return static::$position;
    }
}
