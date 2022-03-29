<?php

declare(strict_types=1);

namespace App;

use App\searchEngineRSSubString;
use App\searchEngineRSRegex;

class searchEngine
{
    const DIRECTORY_A = 'Administration';
    const DIRECTORY_S = 'Storefront';
    const DIRECTORY_C = 'Core';
    const LIMIT_KEEP = 0;
    const LIMIT_DROP = 1;
    const JAVASCRIPT_MODE_NONE = 0;
    const JAVASCRIPT_MODE_REPLACE = 1;
    const JAVASCRIPT_MODE_APPEND = 2;
    const AVAILABLE_FILE_TYPES = [
        'twig' => '.html.twig',
        'js' => '.js',
        'xml' => '.xml',
        'json' => '.json',
        'css' => '.xss',
        'scss' => '.scss',
        'html' => '.html',
        'php' => '.php'
    ];

    private array $selectedFileTypes = [];
    private array $selectedFileTypesLength = [];

    private ?string $limit_keep = null;
    private ?string $limit_drop = null;
    private bool $javaScriptMode = false;
    private bool $javaScriptModeAppend = false;
    private string $shopwareDirectory;

    private string $requiredSubString = '';
    private bool $regexMode = false;

    private array $foundFiles = [];
    private array $foundFilesIndexJs = [];

    public function __construct(
        string $shopwareDirectory,
        string $requiredSubString,
        bool $requiredSubStringRegex = false
    ) {
        $this->shopwareDirectory = $shopwareDirectory;
        $this->requiredSubString = $requiredSubString;
        $this->regexMode = $requiredSubStringRegex;
    }

    public function addFileType(string $type): bool
    {
        if (isset(self::AVAILABLE_FILE_TYPES[$type])) {
            $this->selectedFileTypes[$type] = self::AVAILABLE_FILE_TYPES[$type];
            $this->selectedFileTypesLength[$type] = strlen(self::AVAILABLE_FILE_TYPES[$type]);
            return true;
        }

        return false;
    }

    public function setJavaScriptMode(int $javaScriptMode): void
    {
        if ($javaScriptMode === self::JAVASCRIPT_MODE_REPLACE) {
            $this->javaScriptMode = true;
            $this->javaScriptModeAppend = false;
        } elseif ($javaScriptMode === self::JAVASCRIPT_MODE_APPEND) {
            $this->javaScriptMode = true;
            $this->javaScriptModeAppend = true;
        } else {
            $this->javaScriptMode = false;
            $this->javaScriptModeAppend = false;
        }
    }

    public function setAllFileTypes(): void
    {
        $this->selectedFileTypes = self::AVAILABLE_FILE_TYPES;
        foreach ($this->selectedFileTypes as $type => $value) {
            $this->selectedFileTypesLength[$type] = strlen($value);
        }
    }

    public function listAllowedFileTypes(): string
    {
        $foundFileTypes = [];
        foreach (self::AVAILABLE_FILE_TYPES as $type => $extension) {
            $foundFileTypes[] = $type;
        }
        return implode(', ', $foundFileTypes);
    }

    public function setDirectoryNameLimit(string $string, int $limit): void
    {
        if ($limit === self::LIMIT_KEEP) {
            $this->limit_keep = $string;
        } elseif ($limit === self::LIMIT_DROP) {
            $this->limit_drop = $string;
        }
    }

    public function result(): array
    {
        if ($this->javaScriptMode) {
            if ($this->javaScriptModeAppend) {
                return array_merge($this->foundFiles, $this->foundFilesIndexJs);
            }
            return $this->foundFilesIndexJs;
        }
        return $this->foundFiles;
    }

    public function browseFiles(string $selectedDirectory): void
    {
        $currentDirectory = $this->shopwareDirectory . '/' . $selectedDirectory;
        if (!file_exists($currentDirectory)) {
            output::stdErr("Directory does not exist: $currentDirectory");
            exit(1);
        }

        $this->recursiveDirectoryWalker($currentDirectory);
        $this->applyDirectoryNameLimits();
        $this->dropNonMatchingFiles();
        if ($this->javaScriptMode) {
            $this->locateIndexJsFiles();
        }
    }

    private function recursiveDirectoryWalker(string $currentDirectory, int $depth = 50): void
    {
        $depth--;
        if ($depth <= 0) {
            output::stdErr("Warning! Too much recursion");
            return;
        }

        foreach (scandir($currentDirectory) as $listElement) {
            if (strlen($listElement) < 3) {
                continue;
            }

            $tempDirectoryString = $currentDirectory . '/' . $listElement;

            if (is_link($tempDirectoryString)) {
                // Ignoring symlinks
                continue;
            }

            if (is_file($tempDirectoryString)) {
                if ($this->isAllowedExtension($tempDirectoryString)) {
                    $this->foundFiles[] = $tempDirectoryString;
                }
                continue;
            }

            if (is_dir($tempDirectoryString)) {
                $this->recursiveDirectoryWalker($tempDirectoryString, $depth);
            }
        }
    }

    private function applyDirectoryNameLimits(): void
    {
        if ($this->limit_keep !== null) {
            foreach ($this->foundFiles as $key => $file) {
                if (!(strpos($file, $this->limit_keep)) !== false) {
                    unset($this->foundFiles[$key]);
                }
            }
        }

        if ($this->limit_drop !== null) {
            foreach ($this->foundFiles as $key => $file) {
                if (strpos($file, $this->limit_drop) !== false) {
                    unset($this->foundFiles[$key]);
                }
            }
        }
    }

    private function dropNonMatchingFiles(): void
    {
        if ($this->regexMode) {
            $searchEngineMode = new searchEngineRSRegex($this->requiredSubString);
        } else {
            $searchEngineMode = new searchEngineRSSubString($this->requiredSubString);
        }

        foreach ($this->foundFiles as $key => $file) {
            if (!file_exists($file)) {
                output::stdErr("Could not find a file: $file");
                exit(1);
            }

            if ($searchEngineMode->dropNonMatchingFiles($file)) {
                unset($this->foundFiles[$key]);
            }
        }
    }

    private function isAllowedExtension(string $fileName): bool
    {
        foreach ($this->selectedFileTypes as $type => $extension) {
            if (strcmp($extension, substr($fileName, -($this->selectedFileTypesLength[$type]))) === 0) {
                return true;
            }
        }
        return false;
    }

    private function locateIndexJsFiles(): void
    {
        $indexJsFiles = [];

        foreach ($this->foundFiles as $fileName) {
            if (strcmp(substr($fileName, -9), '/index.js') === 0) {
                if (in_array($fileName, $indexJsFiles, true)) {
                    continue;
                }

                $indexJsFiles[] = $fileName;
                continue;
            }

            $newFileName = preg_replace("/[^\/]+$/", 'index.js', $fileName);
            if (file_exists($newFileName)) {
                if (in_array($newFileName, $indexJsFiles, true)) {
                    continue;
                }

                $indexJsFiles[] = $newFileName;
            }
        }

        $this->foundFilesIndexJs = array_merge($this->foundFilesIndexJs, $indexJsFiles);
    }
}