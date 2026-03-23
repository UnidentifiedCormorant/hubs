<?php

namespace Yourcormorant\LaravelHubs\Exceptions;

use Exception;
use Throwable;

class NoNecessaryImplementationsException extends Exception
{
    public function __construct(
        string $pipe,
        /** @var array<int, string> */
        array $necessaryImplementations,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        $message = "Пайп $pipe должен реализовывать все перечисленные далее интерфейсы:";
        foreach ($necessaryImplementations as $implementation){
            $message .= " $implementation,";
        }

        parent::__construct(substr($message, 0, -1), $code, $previous);
    }
}
