<?php

namespace Yourcormorant\LaravelHubs\Abstracts;

use ReflectionException;
use Yourcormorant\LaravelHubs\Exceptions\NoNecessaryImplementationsException;
use Yourcormorant\LaravelHubs\Exceptions\PositionAlreadyTakenException;

//TODO: Изменение позиции какого-либо из пайпов?

abstract class AbstractPositionableHub extends AbstractHub implements PositionHubbable
{
    protected array $pipeNecessaryImplementations = [
        Pipelineable::class,
        Positionable::class,
    ];

    /**
     * Проверить, есть ли в хабе пайп с такой позицией
     *
     * @param float $position
     * @return bool
     */
    public function hasPosition(float $position): bool
    {
        return count(
            array_filter($this->pipes, fn (string $pipe) => $pipe::getPosition() === $position)
        );
    }

    /**
     * Проверить есть ли в хабе пайп с такой позицией, выплюнуть исключение, если есть
     *
     * @param float $position
     * @return $this
     *
     * @throws PositionAlreadyTakenException
     */
    private function checkUniquePosition(float $position): self
    {
        if($this->hasPosition($position)){
            throw new PositionAlreadyTakenException();
        }

        return $this;
    }

    /**
     * Получить ссылку на пайп с самым большим значением позиции в хабе
     * Если в хабе ещё нет пайпов - будет возвращено null
     *
     * @return class-string<Positionable>|null
     */
    public function getPipeWithMaxPosition(): ?string
    {
        return array_reduce($this->pipes, function (string $previous, string $current) {
            if (!$previous) {
                return $current;
            }
            return ($current::getPosition() > $previous::getPosition()) ? $current : $previous;
        });
    }

    /**
     * Получить ссылку на пайп с самым маленьким значением позиции в хабе
     * Если в хабе ещё нет пайпов - будет возвращено null
     *
     * @return class-string<Positionable>|null
     */
    public function getPipeWithMinPosition(): ?string
    {
        return array_reduce($this->pipes, function (?string $previous, string $current) {
            if (!$previous) {
                return $current;
            }
            return ($current::getPosition() < $previous::getPosition()) ? $current : $previous;
        });
    }

    /**
     * Добавить пайп в хаб сразу с позицией
     *
     * @param class-string<Positionable> $pipe
     * @param float $position
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     * @throws PositionAlreadyTakenException
     * @throws ReflectionException
     */
    public function stickPipe(string $pipe, float $position): self
    {
        $this
            ->checkPipeHasNecessaryImplementations($pipe)
            ->checkUniquePosition($position);

        $this->pipes[] = $pipe::withPosition($position);

        return $this;
    }

    /**
     * Добавить пайп в конец "очереди" с учётом позиционирования
     *
     * @param class-string<Positionable> $pipe
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     * @throws ReflectionException
     */
    public function pushPipe(string $pipe): self
    {
        $this->checkPipeHasNecessaryImplementations($pipe);

        $maxPositionPipe = $this->getPipeWithMaxPosition();

        if(is_null($maxPositionPipe)){
            $this->pipes[] = $pipe::withPosition(0);
        } else {
            $this->pipes[] = $pipe::withPosition($maxPositionPipe::getPosition() + 1);
        }

        return $this;
    }

    /**
     * Добавить пайп в начало "очереди" с учётом позиционирования
     *
     * @param class-string<Positionable> $pipe
     * @return $this
     *
     * @throws NoNecessaryImplementationsException
     * @throws ReflectionException
     */
    public function prependPipe(string $pipe): self
    {
        $this->checkPipeHasNecessaryImplementations($pipe);

        $minPositionPipe = $this->getPipeWithMinPosition();

        if(is_null($minPositionPipe)){
            $this->pipes[] = $pipe::withPosition(0);
        } else {
            $this->pipes[] = $pipe::withPosition($minPositionPipe::getPosition() - 1);
        }

        return $this;
    }

    /**
     * Сортируем пайпы
     *
     * @return $this
     */
    protected function sortPipes(): self
    {
        usort(
            $this->pipes,
            fn (string $current, string $next) => ($current::getPosition() < $next::getPosition()) ? -1 : 1
        );

        return $this;
    }

    /**
     * Переопределяем init, чтобы выполнить сортировку
     *
     * @param PipeObjectable $object
     * @return mixed
     */
    public function init(PipeObjectable $object): mixed
    {
        return $this
            ->setObject($object)
            ->preparePipeline()
            ->sortPipes()
            ->getResult();
    }

    /**
     * Переопределяем, т.к. хотим показывать сразу отсортированные пайпы
     *
     * @param string $message
     * @return never
     */
    public function ddPipes(string $message = "Пайпы отсортированы + номера позиций"): never
    {
        $this->sortPipes();
        parent::ddPipes($message);
    }

    /**
     * Переопределяем, т.к. хотим выводить рядом с названием пайпа номер его позиции
     *
     * @param string $pipe
     * @return string
     */
    protected function getPipeDumpExplanation(string $pipe): string
    {
        return "Позиция: " . $pipe::getPosition() . " $pipe";
    }
}
