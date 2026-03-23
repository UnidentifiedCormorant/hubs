<?php

namespace Yourcormorant\LaravelHubs\Abstracts;

use Yourcormorant\LaravelHubs\Exceptions\NoNecessaryImplementationsException;
use Yourcormorant\LaravelHubs\Exceptions\PositionAlreadyTakenException;

interface PositionHubbable extends Hubbable
{
    /**
     * Добавить пайп в хаб сразу с позицией
     *
     * @param class-string<Positionable> $pipe
     * @param float $position
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     * @throws PositionAlreadyTakenException
     */
    public function stickPipe(string $pipe, float $position): self;
}
