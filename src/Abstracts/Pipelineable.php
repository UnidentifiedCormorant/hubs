<?php

namespace Yourcormorant\LaravelHubs\Abstracts;

use Closure;
use Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;

interface Pipelineable
{
    /**
     * Основой метод для абсолютно любого пайпа в рамках хабов
     *
     * @param PipeObjectable $data
     * @param Closure(mixed): mixed $next
     * @return Closure(mixed): mixed|PipeObjectable
     */
    public function handle(PipeObjectable $data, Closure $next): Closure|PipeObjectable;
}
