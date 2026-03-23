<?php

namespace Yourcormorant\LaravelHubs\Exceptions;

use Exception;

class PositionAlreadyTakenException extends Exception
{
    protected $message = 'Данная позиция уже используется в хабе';
}
