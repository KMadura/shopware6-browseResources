<?php

declare(strict_types=1);

namespace App;

interface searchEngineRSInterface
{
    public function dropNonMatchingFiles(string $fileName): bool;
}