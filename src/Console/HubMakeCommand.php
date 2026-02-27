<?php

namespace Yourcormorant\LaravelHubs\Console;

use Yourcormorant\LaravelHubs\Console\Traits\HubMakeable;
use Illuminate\Console\GeneratorCommand;

class HubMakeCommand extends GeneratorCommand
{
    use HubMakeable;

    //Если true - будет подставляться stub для интерфейса
    private bool $needInterfaceStub = false;

    //Пространство имён + название класса объекта, с которым будет работать хаб, введённое пользователем
    private ?string $objectPath = null;

    protected $signature = 'make:hub
                            {name}
                            {--object= : Класс, который будет использоваться в качестве объекта для пайплайна в хабе. Пример: --object="App\Entities\Entity"}
                            {--no-pipe-interface : Не генерировать интерфейс для будущих пайпов в хабе}';

    protected $description = 'Сгенерировать основные файлы для работы с новым хабом';

    protected function getDefaultNamespace($rootNamespace): string
    {
        //Формируем путь с дополнительной директорией для хаба
        return $rootNamespace.'\Hubs\\'.$this->getHubName($this->argument('name'));
    }

    protected function getStub(): string
    {
        if($this->needInterfaceStub){
            return $this->resolveHubStubPath('/stubs/pipeline-interface-with-object-indication.stub');
        }

        if($this->objectPath){
            return $this->resolveHubStubPath('/stubs/hub-with-object-indication.stub');
        }

        return $this->resolveHubStubPath('/stubs/hub.stub');
    }

    protected function buildClass($name): string
    {
        if($this->objectPath){

            return $this->buildClassWithReplacing(
                [
                    //Получаем название класса объекта
                    '{{ objectClass }}' => $this->getObjectClass($this->objectPath),
                    '{{ objectPath }}' => $this->objectPath,
                ],
                $name
            );
        }

        return parent::buildClass($name);
    }

    public function handle(): ?bool
    {
        $this->objectPath = $this->option('object');
        if(!$this->objectPath){
            $this->objectPath = trim($this->ask('Введите пространство имён + название класса объекта, с которым будет работать хаб (по умолчанию null). Пример: App\Entities\EntityClassName'),'"');
        }

        if(parent::handle() === false){
            return false;
        }

        if(
            !$this->option('no-pipe-interface') &&
            $this->objectPath &&
            $this->confirm('Создать интерфейс для будущих пайпов этого хаба?', false))
        {
            $this->createInterfaceFile();
        }

        return true;
    }

    private function createInterfaceFile(): void
    {
        $name = $this->getHubName($this->argument('name')).'Pipelineable';
        $path = $this->getPath($this->qualifyClass("Abstracts/$name"));

        $this->makeDirectory($path);

        $this->needInterfaceStub = true;

        //Генерируем файл интерфейса
        $this->files->put(
            $path,
            $this->buildClassWithReplacing(
                [
                    '{{ name }}' => $name,
                    '{{ interfaceNamespace }}' => $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')).'\Abstracts',
                    '{{ objectPath }}' => $this->objectPath,
                    '{{ objectClass }}' => $this->getObjectClass($this->objectPath)
                ],
                $name,
            )
        );

        $this->components->info(sprintf('%s [%s] created successfully.', '', $path));
    }
}
