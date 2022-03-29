<?php

declare(strict_types=1);

namespace App;

class searchEngineRSRegex implements searchEngineRSInterface
{
    private string $regex;

    public function __construct(string $regex)
    {
        $this->regex = $regex;
    }

    public function dropNonMatchingFiles(string $fileName): bool
    {
        $file = fopen($fileName, 'r');
        while ($line = fgets($file)) {
            $line = str_replace("\n", '', $line);

            if (preg_match($this->regex, $line)) {
                fclose($file);
                return false;
            }
        }
        fclose($file);
        return true;
    }
}