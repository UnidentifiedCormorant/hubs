<?php

namespace Yourcormorant\LaravelHubs\Console\Traits;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * Вспомогательные методы для генерации файлов в рамках библиотеки
 */
trait HubMakeable
{
    /**
     * Получить путь к заглушкам для файлов библиотеки
     *
     * @param string $path
     * @return string
     */
    private function resolveHubStubPath(string $path): string
    {
        return dirname(__DIR__, 3).$path;
    }

    /**
     * Сгенерировать файл с заменой его фрагментов по входящему массиву по следующему принципу:
     *    ключ элемента в массиве - ТО, ЧТО заменяем
     *    значение - ТО, НА ЧТО заменяем
     *
     * @param array $replacements
     * @param $stubName
     * @return string
     *
     * @throws FileNotFoundException
     */
    private function buildClassWithReplacing(array $replacements, $stubName): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), parent::buildClass($stubName));
    }

    /**
     * Сформировать базовое имя для хаба
     *
     * @param string $nameFormArgument
     * @return string
     */
    private function getHubName(string $nameFormArgument): string
    {
        $normalizedClassName = strtolower($nameFormArgument);

        if(str_contains($normalizedClassName, 'hub')){
            return ucfirst(strstr($normalizedClassName, 'hub', true));
        }

        return ucfirst($normalizedClassName);
    }

    /**
     * Получить название класса из аргумента для объекта
     *
     * @param string $objectPath
     * @return string
     */
    private function getObjectClass(string $objectPath): string
    {
        return ltrim(strrchr($objectPath, '\\'), '\\');
    }
}
