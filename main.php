<?php

/*
 * Author: Krzysztof Madura
 * License: MIT
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\options;
use App\output;
use App\searchEngine;
use App\tools;
use App\openEditor;
use App\shopwareInfo;

//
// Check current location
//

$locatedDirectories = tools::locateBaseDirectory();
if ($locatedDirectories === null) {
    output::stdErr(
        "Could not find /vendor/shopware/platform/src/ directory\nAre you sure this script is executed in right location?"
    );
    exit(1);
}

//
// Setting up console options
//

$options = new options();

$options->setOption('a', 'administration', 'browse Administration directory');
$options->setOption('s', 'storefront', 'browse Storefront directory');
$options->setOption('c', 'core', 'browse Core directory');
$options->setOption('js', 'javascript', 'find and list matching index.js files', options::VALUE_OPTIONAL);
$options->addAnotherLine('append word "both" to also list index.js files');
$options->setOption('o', 'open', 'open files in default editor');
$options->setOption('p', 'phpstorm', 'open files in phpstorm editor');
$options->setOption('r', 'regex', 'use perl regex');
$options->setOption('l', 'limit', 'limit to matching files and directories', options::VALUE_REQUIRED);
$options->setOption('d', 'drop', 'drop matching files and directories', options::VALUE_REQUIRED);
$options->setOption('t', 'type', 'list files with particular type', options::VALUE_REQUIRED);
$options->addAnotherLine('accepted file types: css, html, js, json, php, scss, twig, xml');
$options->setOption('v', 'verbose', 'print out more information');
$options->setOption('h', 'help', 'prints out this page');
$options->setOption('i', 'info', 'prints out information about shopware installation');
$options->setDescription('browseResources is a simple shopware 6 resource browser app');

$options->parse($argv);

if ($options->isOption('h')) {
    $options->printHelp();
    exit(0);
}

if ($options->isOption('i')) {
    $info = new shopwareInfo($locatedDirectories->base);
    $info->showPHPVersion();
    $info->showHostOrDocker();
    $info->showShopwareEnv();
    exit(0);
}

$verbose = $options->isOption('v');

// Checking user defined directories

$directoriesToLookFor = [];
if ($options->isOption('a')) {
    $directoriesToLookFor[] = searchEngine::DIRECTORY_A;
}
if ($options->isOption('s')) {
    $directoriesToLookFor[] = searchEngine::DIRECTORY_S;
}
if ($options->isOption('c')) {
    $directoriesToLookFor[] = searchEngine::DIRECTORY_C;
}
if (empty($directoriesToLookFor)) {
    output::stdErr("Error: Please select at least a, c or s option");
    exit(1);
}
if ($verbose) {
    output::stdOut("Searching within directories: " . implode(', ', $directoriesToLookFor));
}

// Managing input text

$stringSearchingFor = $options->getValueString();
$stringRegexMode = $options->isOption('r');
if (empty($stringSearchingFor)) {
    output::stdErr("Error: Please specify text to search for within files");
    exit(1);
}
if ($verbose) {
    if ($stringRegexMode) {
        output::stdOut("Searching for regex: " . $stringSearchingFor);
    } else {
        output::stdOut("Searching for value: " . $stringSearchingFor);
    }
}
if ($stringRegexMode && !tools::verifyRegex($stringSearchingFor)) {
    output::stdErr("Error: Supplied regex string is invalid");
    exit(1);
}

// Locating PHP Storm

$openFile = new openEditor(__DIR__);
if ($options->isOption('p')) {
    if (!$openFile->locatePHPStorm()) {
        output::stdErr(
            "Could not locate PHPStorm.\nPlease create or edit .browseResourcesPhpStormDir in script's directory with complete path to phpstorm.sh file."
        );
        exit(1);
    }
}
if ($verbose) {
    if ($options->isOption('p')) {
        output::stdOut("Files will be opened using PHPStorm editor");
    } elseif ($options->isOption('o')) {
        output::stdOut("Files will be opened using default editor");
    }
}

//
// Search Engine
//

$searchEngine = new searchEngine($locatedDirectories->shopware, $options->getValueString(), $options->isOption('r'));

// Search Engine: Set file types

if (!$options->isOption('t')) {
    if ($verbose) {
        output::stdOut("Checking all file types");
    }
    $searchEngine->setAllFileTypes();
} else {
    $fileType = $options->getOption('t')->value;
    if (!$searchEngine->addFileType($fileType)) {
        output::stdErr("Error: Please use one of listed file types: " . $searchEngine->listAllowedFileTypes());
        exit(1);
    }
    if ($verbose) {
        output::stdOut("Checking file type: " . $fileType);
    }
}

// Search Engine: Apply directory name limits

if ($options->isOption('l')) {
    $searchEngine->setDirectoryNameLimit($options->getOption('l')->value, searchEngine::LIMIT_KEEP);
}
if ($options->isOption('d')) {
    $searchEngine->setDirectoryNameLimit($options->getOption('d')->value, searchEngine::LIMIT_DROP);
}

// Search Engine: Apply JavaScript mode

if ($options->isOption('js')) {
    if ($options->getOption('js')->value === 'both') {
        if ($verbose) {
            output::stdOut("Looking also for appropriate index.js files");
        }
        $searchEngine->setJavaScriptMode(searchEngine::JAVASCRIPT_MODE_APPEND);
    } else {
        if ($verbose) {
            output::stdOut("Looking for appropriate index.js files");
        }
        $searchEngine->setJavaScriptMode(searchEngine::JAVASCRIPT_MODE_REPLACE);
    }
}

// Search Engine: Look up for files

foreach ($directoriesToLookFor as $directory) {
    if ($verbose) {
        output::stdOut("Looking for $directory");
    }
    $searchEngine->browseFiles($directory);
}

// Search Engine: Return results

$result = $searchEngine->result();

// Print out files
if ($verbose) {
    output::stdOut("Number of files: " . count($result));
    $counter = 0;
    foreach ($result as $fileName) {
        $counter++;
        tools::printOutFile($fileName, $counter);
    }
} else {
    foreach ($result as $fileName) {
        tools::printOutFile($fileName);
    }
}

// Open files
if ($options->isOption('p')) {
    if ($verbose) {
        if (tools::dockerCheck()) {
            output::stdOut("Warning! Functionality may not work inside docker container");
        }
        output::stdOut("Opening files using PHPStorm editor");
    }
    foreach ($result as $fileName) {
        $openFile->openUsingPHPStorm($fileName);
    }
} elseif ($options->isOption('o')) {
    if ($verbose) {
        if (tools::dockerCheck()) {
            output::stdOut("Warning! Functionality may not work inside docker container");
        }
        output::stdOut("Opening files using default editor");
    }
    foreach ($result as $fileName) {
        $openFile->openUsingDefaultEditor($fileName);
    }
}

exit(0);