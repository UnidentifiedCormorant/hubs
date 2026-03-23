<?php

namespace Yourcormorant\LaravelHubs\Abstracts;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionException;
use Yourcormorant\LaravelHubs\Exceptions\NoNecessaryImplementationsException;

abstract class AbstractHub implements Hubbable
{
    /** @var array<int, string> */
    protected array $pipes = [];

    protected PipeObjectable $object;

    private Pipeline $pipeline;

    /**
     * Набор интерфейсов, которые обязан реализовывать пайп, иначе его не пропустят в хаб
     *
     * @var array<int, string>
     */
    protected array $pipeNecessaryImplementations = [
        Pipelineable::class,
    ];

    /**
     * @param Container|null $container
     *
     * @throws NoNecessaryImplementationsException
     * @throws ReflectionException
     */
    public function __construct(?Container $container = null)
    {
        $this->pipeline = new Pipeline($container);

        foreach ($pipes = $this->getPipes() as $pipe){
            $this->checkPipeHasNecessaryImplementations($pipe);
        }

        $this->pipes = $pipes;
    }

    /** @var array<int, string> */
    abstract protected function getPipes(): array;

    /**
     * Проверить реализует ли пайп необходимые интерфейсы перед добавлением в хаб
     *
     * @param string $pipe
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     * @throws ReflectionException
     */
    protected function checkPipeHasNecessaryImplementations(string $pipe): self
    {
        $reflectedPipe = new ReflectionClass($pipe);
        foreach ($this->pipeNecessaryImplementations as $implementation){

            if(!$reflectedPipe->implementsInterface($implementation)){
                throw new NoNecessaryImplementationsException(
                    $reflectedPipe->getNamespaceName(),
                    $this->pipeNecessaryImplementations
                );
            }

        }
        return $this;
    }

    /**
     * Добавить новый пайп в конец массива с пайпами
     *
     * @param class-string<Pipelineable> $pipe
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     * @throws ReflectionException
     */
    public function pushPipe(string $pipe): self
    {
        $this->checkPipeHasNecessaryImplementations($pipe);

        $this->pipes[] = $pipe;

        return $this;
    }

    /**
     * Добавить новый пайп в начало массива с пайпами
     *
     * @param class-string<Pipelineable> $pipe
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     * @throws ReflectionException
     */
    public function prependPipe(string $pipe): self
    {
        $this->checkPipeHasNecessaryImplementations($pipe);

        array_unshift($this->pipes, $pipe);

        return $this;
    }

    /**
     * Инициализировать список пайпов
     * Пайпы, что лежали в хабе до этого, будут утеряны!
     *
     * @param array $pipes
     * @return void
     *
     * @throws NoNecessaryImplementationsException
     * @throws ReflectionException
     */
    public function collectPipes(array $pipes)
    {
        foreach ($pipes as $pipe){
            $this->checkPipeHasNecessaryImplementations($pipe);
        }

        $this->pipes = $pipes;
    }

    /**
     * Явно определить объект для данного хаба
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
     * Запустить пайплайн и получить результат его работы
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
     * @return mixed
     */
    public function init(PipeObjectable $object): mixed
    {
        return $this
            ->setObject($object)
            ->preparePipeline()
            ->getResult();
    }

    /**
     * Выполнить пайплайн из хаба в рамках транзакции и получить результат
     *
     * @param PipeObjectable $object
     * @return mixed
     */
    public function initWithTransaction(PipeObjectable $object): mixed
    {
        return DB::transaction(
            fn () => $this->init($object)
        );
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
        return $withTransaction
            ? $this->initWithTransaction($object)
            : $this->init($object);
    }

    /**
     * Вывести список хабов и завершить скрипт
     *
     * @param string $message
     * @return never
     */
    public function ddPipes(string $message = "Список пайпов, что покоятся в хабе на данный момент"): never
    {
        dd(
            $message,
            array_map(
                fn($pipe) => $this->getPipeDumpExplanation($pipe),
                $this->pipes
            )
        );
    }

    /**
     * Пояснение к пайпу
     *
     * @param string $pipe
     * @return string
     */
    protected function getPipeDumpExplanation(string $pipe): string
    {
        return $pipe;
    }
}
