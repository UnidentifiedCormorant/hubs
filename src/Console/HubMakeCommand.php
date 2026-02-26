<?php

namespace Yourcormorant\LaravelHubs\Console;

use Yourcormorant\LaravelHubs\Console\Traits\HubMakeable;
use Illuminate\Console\GeneratorCommand;

class HubMakeCommand extends GeneratorCommand
{
    use HubMakeable;

    protected $signature = 'make:hub
                            {name}
                            {--object= : Класс, который будет использоваться в качестве объекта для пайплайна в хабе. Пример: --object="App\Entities\Entity"}
                            {--no-pipe-interface : Не генерировать интерфейс для будущих пайпов в хабе}';

    protected $description = 'Сгенерировать файлы и каталоги для работы с новым хабом';

    protected function getDefaultNamespace($rootNamespace): string
    {
        $normalizedClassName = strtolower($this->argument('name'));

        //Формируем название дополнительной директории для хаба
        if(str_contains($normalizedClassName, 'hub')){
            $hubDir = ucfirst(strstr($normalizedClassName, 'hub', true));
        } else {
            $hubDir = ucfirst($normalizedClassName);
        }

        return $rootNamespace.'\Hubs\\'.$hubDir;
    }

    protected function getStub(): string
    {
        if($this->option('object')){
            return $this->resolveHubStubPath('/stubs/hub-with-object-indication.stub');
        }

        return $this->resolveHubStubPath('/stubs/hub.stub');
    }

    protected function buildClass($name): string
    {
        if($objectPath = $this->option('object')){

            return $this->buildClassWithReplacing(
                [
                    //Получаем название класса объекта
                    '{{ objectClass }}' => ltrim(strrchr($objectPath, '\\'), '\\'),
                    '{{ objectPath }}' => $objectPath,
                ],
                $name
            );
        }

        return parent::buildClass($name);
    }

    public function handle(): ?bool
    {
        //TODO: Спрашивать про объект, если он не передан
        parent::handle();

        //TODO: Если объект не передан - создаём базовый интерфейс без объекта
        if(
            !$this->option('no-pipe-interface') &&
            $this->option('object') &&
            $this->confirm('Создать интерфейс для будущих пайпов этого хаба?', false))
        {
            $interfacePath = $this->getPath($this->qualifyClass('Abstracts/TestPipelinable'));

            $this->makeDirectory($interfacePath);

            //TODO: Вынести в отдельную команду
            //Создаём файл интерфейса
            $this->files->put(
                $interfacePath,
                $this->buildClassWithReplacing(
                    [
                        '{{ name }}' => 'iiiiiiiiiiiiiii',
                        '{{ namespace }}' => 'oooooooooooo',
                        '{{ objectPath }}' => 'aaaaaaaaaa',
                        '{{ objectClass }}' => 'mmmmmmmmmm'
                    ],
                    $this->resolveHubStubPath('/stubs/pipeline-interface.stub'),
                )
            );
        }

        //TODO: Создать директорию под пайпы

        return null;
    }
}
