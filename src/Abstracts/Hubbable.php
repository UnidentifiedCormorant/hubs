<?php

namespace Yourcormorant\LaravelHubs\Abstracts;

use Yourcormorant\LaravelHubs\Exceptions\NoNecessaryImplementationsException;

interface Hubbable
{
    /**
     * Выполнить пайплайн из хаба и получить результат
     *
     * @param PipeObjectable $object
     * @return mixed
     */
    public function init(PipeObjectable $object): mixed;

    /**
     * Выполнить пайплайн из хаба в рамках транзакции и получить результат
     *
     * @param PipeObjectable $object
     * @return mixed
     */
    public function initWithTransaction(PipeObjectable $object): mixed;

    /**
     * Добавить новый пайп в конец массива с пайпами
     *
     * @param string $pipe
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     */
    public function pushPipe(string $pipe): self;

    /**
     * Добавить новый пайп в начало массива с пайпами
     *
     * @param string $pipe
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     */
    public function prependPipe(string $pipe): self;

    /**
     * Инициализировать список пайпов
     *
     * @param array $pipes
     * @return void
     *
     * @throws NoNecessaryImplementationsException
     */
    public function collectPipes(array $pipes);
}
