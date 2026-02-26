<?php

namespace Yourcormorant\LaravelHubs\Console\Traits;

trait HubMakeable
{
    private function resolveHubStubPath(string $path): string
    {
        return dirname(__DIR__, 3) . $path;
    }

    /**
     * Сгенерировать файл с заменой его фрагментов по входящему массиву
     *
     * @param array $replacements
     * @param $stubName
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function buildClassWithReplacing(array $replacements, $stubName): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), parent::buildClass($stubName));
    }
}
