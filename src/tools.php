<?php

declare(strict_types=1);

namespace App;

class tools
{
    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    public static function verifyRegex(string $regex): bool
    {
        return !(@preg_match($regex, '') === false);
    }

    public static function printOutFile(string $fileName, int $number = 0): void
    {
        output::stdOut(($number > 0 ? "$number. " : '') . preg_replace('/^.+?(?=\/src)/', '', $fileName));
    }

    public static function stripOffPhar(string $directory): string
    {
        return preg_replace('/(?:phar:\/\/|[^\/]+\.phar.*$)/', '', $directory);
    }

    public static function locateBaseDirectory(): ?object
    {
        // checking current working directory
        $baseDirectory = self::stripOffPhar(getcwd());
        $extendedDirectory = $baseDirectory . '/vendor/shopware/platform/src';
        if (file_exists($extendedDirectory)) {
            return (object)['base' => $baseDirectory, 'shopware' => $extendedDirectory];
        }

        // checking script file directory
        $baseDirectory = self::stripOffPhar(__DIR__);
        $extendedDirectory = $baseDirectory . '/vendor/shopware/platform/src';
        if (file_exists($baseDirectory)) {
            return (object)['base' => $baseDirectory, 'shopware' => $extendedDirectory];
        }

        return null;
    }

    public static function dockerCheck(): bool
    {
        return file_exists('/.dockerenv');
    }
}