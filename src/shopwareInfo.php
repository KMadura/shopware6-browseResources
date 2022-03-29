<?php

declare(strict_types=1);

namespace App;

class shopwareInfo
{
    private string $baseDirectory;

    public function __construct(string $baseDirectory)
    {
        $this->baseDirectory = tools::stripOffPhar($baseDirectory);
    }

    private function parseEnv(): object
    {
        $shopwareParams = [];
        $maxKeyLength = 0;

        $file = fopen($this->baseDirectory . '/.env', 'r');
        while ($line = fgets($file)) {
            if (preg_match('/^([^#=]+)=(.*)[\n\s]*/', $line, $matches)) {
                $shopwareParams[$matches[1]] = $matches[2];
                if (strlen($matches[1]) > $maxKeyLength) {
                    $maxKeyLength = strlen($matches[1]);
                }
            }
        }

        return (object)["params" => $shopwareParams, "strpad" => $maxKeyLength];
    }

    public function showPHPVersion(): void
    {
        output::stdOut("Current CLI version of PHP: " . phpversion());
    }

    public function showShopwareEnv(): void
    {
        if (!file_exists($this->baseDirectory . '/.env')) {
            output::stdOut("Could not find .env file");
            return;
        }

        $envData = $this->parseEnv();

        foreach ($envData->params as $key => $value) {
            output::stdOut(str_pad($key, $envData->strpad, ' ') . ' : ' . $value);
        }
    }

    public function showHostOrDocker(): void
    {
        if (tools::dockerCheck()) {
            output::stdOut("Running from a docker container");
        } else {
            output::stdOut("Running on a host machine");
        }
    }
}