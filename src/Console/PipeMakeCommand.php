<?php

namespace Yourcormorant\LaravelHubs\Console;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;
use Yourcormorant\LaravelHubs\Console\Traits\HubMakeable;
use Illuminate\Console\GeneratorCommand;

class PipeMakeCommand extends GeneratorCommand
{
    use HubMakeable;

    //Пространство имён + название хаба, для которого будем создавать пайп
    private ?string $hubNamespace = null;

    /** @var array<string, string> */
    private array $replacements = [];

    //Определяет, используются ли объекты в сигнатуре метода пайпа
    private bool $hasObjects = false;

    protected $signature = 'make:pipe
                            {name : Название пайпа}
                            {hub? : Название хаба, для которого будет создан пайп. Пример 1: CreateOrderHub, Пример 2: "App\Hubs\CreateOrder\CreateOrderHub"}
                            {--free : Пайп не будет привязан к хабу}';

    protected $description = 'Сгенерировать пайп для хаба';

    protected function getDefaultNamespace($rootNamespace): string
    {
        if($this->option('free') || !$this->hubNamespace){
            return "$rootNamespace\\Hubs\\Pipes";
        }

        return $this->getPathFromNamespace($this->hubNamespace).'\\Pipes';
    }

    protected function getStub(): string
    {
        return !$this->hubNamespace
            ? $this->resolveHubStubPath('/stubs/pipe.stub')
            : $this->resolveHubStubPath('/stubs/pipe-completed.stub');
    }

    protected function buildClass($name): string
    {
        return $this->buildClassWithReplacing($this->replacements, $name);
    }

    public function handle()
    {
        if(!$this->option('free')){
            $this->resolveHub();
        }

        if($this->hubNamespace){
            $this->resolveReplacements();
        }

        return parent::handle();
    }

    /**
     * Определяем неймспейс хаба, в котором будет создавать пайплайн
     *
     * @return void
     */
    private function resolveHub(): void
    {
        if(str_contains($this->argument('hub'), '\\')){
            $this->hubNamespace = $this->argument('hub');
        } else {
            $hubsDefaultDir = $this->laravel['path'].'/Hubs';
            $hubFileName = $this->argument('hub').'.php';

            $suitableFiles = glob($hubsDefaultDir . '/**/' . $hubFileName);

            if(!$this->argument('hub')) {
                $this->hubNamespace = trim($this->ask('Не указан хаб. Пожалуйста, введите пространство имён + название хаба (допустимо null). Пример: App\Hubs\Order\CreateOrderHub'), '"');
            } elseif(count($suitableFiles) > 1){
                $this->hubNamespace = trim($this->ask('Найдено несколько хабов с таким названием. Пожалуйста, введите пространство имён + название хаба (допустимо null). Пример: App\Hubs\Order\CreateOrderHub'), '"');
            } elseif(count($suitableFiles) === 0) {
                $this->hubNamespace = trim($this->ask('Не найдено хабов с таким названием. Пожалуйста, введите пространство имён + название хаба (допустимо null). Пример: App\Hubs\Order\CreateOrderHub'), '"');
            } else {
                $suitableHubNamespace = str_replace(['app', '.php', '/'], ['App', '', '\\'], strrchr($suitableFiles[0], 'app'));

                $this->hubNamespace = class_exists($suitableHubNamespace)
                    ? $suitableHubNamespace
                    : trim($this->ask('Хаб не найден. Пожалуйста, введите пространство имён + название хаба (допустимо null). Пример: App\Hubs\Order\CreateOrderHub'), '"');
            }
        }

        if(empty($this->hubNamespace)){
            $this->hubNamespace = null;
        }
    }

    /**
     * Собрать ключевые элементы для генерации файла
     *
     * @return void
     *
     * @throws ReflectionException
     */
    private function resolveReplacements(): void
    {
        $reflection = new ReflectionClass($this->hubNamespace);

        /** @var array<int, ReflectionNamedType> $setObjectMethodTypes */
        $setObjectMethodTypes = $reflection->getMethod('setObject')->getParameters()[0]->getType() instanceof ReflectionUnionType
            ? $reflection->getMethod('setObject')->getParameters()[0]->getType()->getTypes()
            : [$reflection->getMethod('setObject')->getParameters()[0]->getType()];

        if(count($setObjectMethodTypes) > 1){
            $objectTypesUses = '';
            $objectUnionType = '';

            foreach ($setObjectMethodTypes as $type) {
                $objectTypesUses .= "\nuse {$type->getName()};";
                $objectUnionType .= "|{$this->getClassFromNamespace($type->getName())}";
            }

            $this->replacements = [
                '{{ uses }}' => $objectTypesUses,
                '{{ objectUnionType }}' => trim($objectUnionType, '|'),
            ];

            $this->hasObjects = true;
        } else {
            $this->replacements = [
                '{{ uses }}' => "\nuse Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;",
                '{{ objectUnionType }}' => 'PipeObjectable',
            ];
        }

        $interfaceSuitableNamespace = $this->getPathFromNamespace($this->hubNamespace)."\Abstracts\\".$this->getHubName($this->getClassFromNamespace($this->hubNamespace)).'Pipelineable';

        $interfaceNamespace = file_exists($this->getPath($interfaceSuitableNamespace)) && !$this->option('free')
            ? $interfaceSuitableNamespace
            : trim($this->ask('Интерфейс для хаба не найден. Введите пространство имён + название интерфейса, который реализует пайп (допустимо null). Пример: App\Hubs\Order\Abstracts\OrderPipelineable'));

        if($interfaceNamespace && $this->hasObjects){
            $this->replacements['{{ uses }}'] .= "\nuse $interfaceNamespace;";
            $this->replacements['{{ interfaceImplements }}'] = " implements {$this->getClassFromNamespace($interfaceNamespace)}";
        } else {
            $this->replacements['{{ interfaceUse }}'] = '';
            $this->replacements['{{ interfaceImplements }}'] = '';
        }
    }
}
