<?php

declare(strict_types=1);

namespace App;

use App\tools;

class openEditor
{
    private string $locatedPHPStorm = '';
    private string $baseDirectory;

    public function __construct(string $baseDirectory)
    {
        $this->baseDirectory = tools::stripOffPhar($baseDirectory);
    }

    public function openUsingDefaultEditor(string $file): void
    {
        @exec('nohup open "' . $file . '" > /dev/null 2>&1 &');
    }

    public function openUsingPHPStorm(string $file): void
    {
        if (empty($this->locatedPHPStorm)) {
            return;
        }
        @exec('nohup ' . tools::addSlashes($this->locatedPHPStorm) . ' "' . $file . '" > /dev/null 2>&1 &');
    }

    public function locatePHPStorm(): bool
    {
        // Look for existing settings
        if (file_exists($this->baseDirectory . '/.browseResourcesPhpStormDir')) {
            $this->locatedPHPStorm = trim(file_get_contents($this->baseDirectory . '/.browseResourcesPhpStormDir'));

            if (file_exists($this->locatedPHPStorm)) {
                return true;
            }
        }

        // Look for locate command
        if (file_exists('/usr/bin/locate')) {
            $this->locatedPHPStorm = trim(shell_exec('locate bin/phpstorm.sh --wholename') ?? '');

            if (!empty($this->locatedPHPStorm) && file_exists($this->locatedPHPStorm)) {
                $this->saveLocatedPHPStorm();
                return true;
            }
        }

        // Look for update-alternatives
        if (file_exists('/usr/bin/update-alternatives')) {
            $this->locatedPHPStorm = trim(
                shell_exec('update-alternatives --list editor | grep phpstorm | head -1') ?? ''
            );

            if (!empty($this->locatedPHPStorm) && file_exists($this->locatedPHPStorm)) {
                $this->saveLocatedPHPStorm();
                return true;
            }
        }

        // Maybe it is running right now?
        if (file_exists('/usr/bin/ps') && file_exists('/usr/bin/awk')) {
            $this->locatedPHPStorm = trim(
                shell_exec('ps aux | grep bin/phpstorm.sh | grep -v grep | head -1 | awk \'{print $NF}\'') ?? ''
            );

            if (!empty($this->locatedPHPStorm) && file_exists($this->locatedPHPStorm)) {
                $this->saveLocatedPHPStorm();
                return true;
            }
        }

        return false;
    }

    private function saveLocatedPHPStorm(): void
    {
        @file_put_contents($this->baseDirectory . '/.browseResourcesPhpStormDir', $this->locatedPHPStorm);
    }
}