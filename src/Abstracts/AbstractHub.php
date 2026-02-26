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
     * Здесь можно расширить стандартный список пайпов с помощью дополнительной логики
     *
     * @param PipeObjectable $object
     * @return void
     */
    protected function expandPipes(PipeObjectable $object): void {}

    /**
     * Запустить пайплайн
     *
     * @return $this
     */
    public function initPipeline(): self
    {
        $this->expandPipes($this->object);

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
     * Сахар, если нужно вызвать методы в штатном режиме
     *
     * @param PipeObjectable $object
     * @param bool $withTransaction
     * @return mixed
     */
    public function __invoke(PipeObjectable $object, bool $withTransaction = false): mixed
    {
        $pipeline = fn () => $this
            ->setObject($object)
            ->initPipeline()
            ->getResult();

        if($withTransaction){
            return DB::transaction($pipeline);
        }

        return $pipeline();
    }
}
