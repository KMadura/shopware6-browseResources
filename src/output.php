<?php

declare(strict_types=1);

namespace App;

class output
{
    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    public static function stdOut(string $string): void
    {
        @fwrite(STDOUT, "$string\n");
    }

    public static function stdErr(string $string): void
    {
        @fwrite(STDERR, "$string\n");
    }
}