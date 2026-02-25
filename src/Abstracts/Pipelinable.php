<?php

namespace Yourcormorant\LaravelHubs\Abstracts;

use Closure;
use Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;

interface Pipelinable
{
    /**
     * Основой метод для абсолютно любого пайпа в рамках хабов
     *
     * @param PipeObjectable $data
     * @param Closure $next
     * @return Closure|PipeObjectable
     */
    public function handle(PipeObjectable $data, Closure $next): Closure|PipeObjectable;
}
