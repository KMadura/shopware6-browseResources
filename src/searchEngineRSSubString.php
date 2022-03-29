<?php

declare(strict_types=1);

namespace App;

class searchEngineRSSubString implements searchEngineRSInterface
{
    private string $subString;
    private int $subStringLength;

    public function __construct(string $subString)
    {
        $this->subString = $subString;
        $this->subStringLength = strlen($subString);
    }

    public function dropNonMatchingFiles(string $fileName): bool
    {
        $file = fopen($fileName, 'r');
        while ($line = fgets($file)) {
            if (strlen($line) < $this->subStringLength) {
                continue;
            }

            if (strpos($line, $this->subString) !== false) {
                fclose($file);
                return false;
            }
        }
        fclose($file);
        return true;
    }
}