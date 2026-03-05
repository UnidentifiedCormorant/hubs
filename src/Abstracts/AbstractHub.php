<?php

namespace Yourcormorant\LaravelHubs\Abstracts;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;

abstract class AbstractHub
{
    /** @var array<int, string> */
    protected array $pipes = [];

    protected PipeObjectable $object;

    private Pipeline $pipeline;

    public function __construct(?Container $container = null)
    {
        $this->pipeline = new Pipeline($container);
    }

    /**
     * Явно определить объект в классе-наследнике
     *
     * @param PipeObjectable $object
     * @return $this
     */
    public function setObject(PipeObjectable $object): self
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Подготовить пайплайн к запуску
     *
     * @return $this
     */
    public function preparePipeline(): self
    {
        $this->pipeline
            ->send($this->object)
            ->through($this->pipes);

        return $this;
    }

    /**
     * Получить результат работы пайплайна
     *
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->pipeline->thenReturn();
    }

    /**
     * Выполнить пайплайн из хаба и получить результат
     *
     * @param PipeObjectable $object
     * @param bool $withTransaction
     * @return mixed
     */
    public function init(PipeObjectable $object, bool $withTransaction = false): mixed
    {
        $pipeline = fn () => $this
            ->setObject($object)
            ->preparePipeline()
            ->getResult();

        if($withTransaction){
            return DB::transaction($pipeline);
        }

        return $pipeline();
    }

    /**
     * Сахар, если хотим вызвать как функцию
     *
     * @param PipeObjectable $object
     * @param bool $withTransaction
     * @return mixed
     */
    public function __invoke(PipeObjectable $object, bool $withTransaction = false): mixed
    {
        return $this->init($object, $withTransaction);
    }
}
